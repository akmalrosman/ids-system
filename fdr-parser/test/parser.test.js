import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseBuffer, FdrError } from '../src/index.js';
import { parseCsv, splitHeader } from '../src/lib/csv.js';
import { parseLocalDateTime, parseOffset, parseDurationText, toIsoWithOffset } from '../src/lib/time.js';
import { toSI } from '../src/lib/units.js';
import { looksBinary } from '../src/adapters/dji-binary.js';

const DJI_CSV = [
    'CUSTOM.date [local],CUSTOM.updateTime [local],OSD.flyTime,OSD.flyTime [s],OSD.latitude,OSD.longitude,OSD.height [ft],OSD.hSpeed [MPH],OSD.zSpeed [MPH],OSD.pitch,OSD.roll,OSD.yaw,OSD.flycState,BATTERY.chargeLevel,APP.tip',
    '5/18/2025,4:00:00.00 PM,0m 0.0s,0.0,0,0,0,0,0,0,0,0,P-GPS,99,', // no GPS fix yet -> dropped
    '5/18/2025,4:00:01.00 PM,0m 1.0s,1.0,6.3297,99.7287,0,0,0,0,0,10,P-GPS,99,"Taking off, stand clear"',
    '5/18/2025,4:00:02.00 PM,0m 2.0s,2.0,6.3298,99.7287,32.8,22.4,-11.2,-10,2,10,AutoTakeoff,98,',
    '5/18/2025,4:00:03.00 PM,0m 3.0s,3.0,6.3299,99.7288,65.6,22.4,0,-12,1,-170,P-GPS,98,',
].join('\r\n');

test('csv parser handles quoted commas', () => {
    assert.deepEqual(parseCsv('a,b\r\n1,"x, y"\r\n'), [['a', 'b'], ['1', 'x, y']]);
});

test('headers are split into name and unit', () => {
    assert.deepEqual(splitHeader('OSD.height [ft]'), { name: 'OSD.height', unit: 'ft' });
    assert.deepEqual(splitHeader('speed(mph)'), { name: 'speed', unit: 'mph' });
    assert.deepEqual(splitHeader('OSD.pitch'), { name: 'OSD.pitch', unit: null });
});

test('time helpers', () => {
    assert.equal(parseOffset('+08:00'), 480);
    assert.equal(parseDurationText('1m 2.5s'), 62.5);
    const ms = parseLocalDateTime('5/18/2025', '4:00:00.10 PM', 480);
    assert.equal(new Date(ms).toISOString(), '2025-05-18T08:00:00.100Z');
    assert.equal(toIsoWithOffset(ms, 480), '2025-05-18T16:00:00.100+08:00');
});

test('unit conversion', () => {
    assert.equal(toSI(100, 'ft', 'length'), 30.48);
    assert.ok(Math.abs(toSI(10, 'MPH', 'speed') - 4.4704) < 1e-9);
});

test('parses a DJI CSV flight record into telemetry', () => {
    const out = parseBuffer(Buffer.from(DJI_CSV), { fileName: 'test.txt', tz: '+08:00', maxRate: 10 });

    assert.equal(out.format, 'ids-telemetry');
    assert.equal(out.source.adapter, 'dji-csv');
    assert.equal(out.startTime, '2025-05-18T16:00:01.000+08:00');
    assert.equal(out.duration, 2);
    assert.deepEqual(out.channels.t, [0, 1, 2]);
    assert.equal(out.channels.alt[1], 10); // 32.8 ft -> 10 m
    assert.ok(Math.abs(out.channels.vs[1] - 5) < 0.02); // zSpeed is NED: -11.2 mph -> +5 m/s climb
    assert.equal(out.channels.yaw[2], 190); // -170 -> 0..360
    assert.deepEqual(
        out.events.map((e) => e.label),
        ['P-GPS', 'AutoTakeoff', 'P-GPS'],
    );
    assert.equal(out.stats.home.lat, 6.3297);
});

test('generic CSV with lat/lon columns is accepted', () => {
    const csv = 'time,lat,lon,alt\n0,5.79,102.48,0\n1,5.7901,102.48,10\n';
    const out = parseBuffer(Buffer.from(csv));
    assert.equal(out.source.adapter, 'generic-csv');
    assert.equal(out.stats.samples, 2);
    assert.equal(out.startTime, null);
});

test('unknown files raise UNKNOWN_FORMAT', () => {
    assert.throws(
        () => parseBuffer(Buffer.from('foo,bar\n1,2\n')),
        (e) => e instanceof FdrError && e.code === 'UNKNOWN_FORMAT',
    );
});

test('binary DJI logs need the external decoder', () => {
    const binary = Buffer.from([0x40, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x8d, 0x00, 0x0e, 0x01, 0x02, 0x03]);
    assert.ok(looksBinary(binary));
    assert.throws(
        () => parseBuffer(binary),
        (e) => e instanceof FdrError && e.code === 'DJI_BINARY_NOT_CONFIGURED',
    );
});
