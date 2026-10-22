/**
 * Minimal RFC 4180 CSV parser (quoted fields, "" escapes, CRLF/LF).
 * Returns an array of rows, each row an array of strings.
 *
 * @param {string} text
 * @returns {string[][]}
 */
export function parseCsv(text) {
    const rows = [];
    let row = [];
    let field = '';
    let inQuotes = false;

    for (let i = 0; i < text.length; i++) {
        const ch = text[i];

        if (inQuotes) {
            if (ch === '"') {
                if (text[i + 1] === '"') {
                    field += '"';
                    i++;
                } else {
                    inQuotes = false;
                }
            } else {
                field += ch;
            }
            continue;
        }

        if (ch === '"') {
            inQuotes = true;
        } else if (ch === ',') {
            row.push(field);
            field = '';
        } else if (ch === '\n' || ch === '\r') {
            if (ch === '\r' && text[i + 1] === '\n') i++;
            row.push(field);
            rows.push(row);
            row = [];
            field = '';
        } else {
            field += ch;
        }
    }

    if (field !== '' || row.length > 0) {
        row.push(field);
        rows.push(row);
    }

    return rows.filter((r) => !(r.length === 1 && r[0].trim() === ''));
}

/**
 * Splits a header like "OSD.height [ft]" or "speed(mph)" into name + unit.
 *
 * @param {string} header
 * @returns {{ name: string, unit: string|null }}
 */
export function splitHeader(header) {
    const clean = header.trim();
    const match = clean.match(/^(.*?)\s*(?:\[([^\]]+)\]|\(([^)]+)\))\s*$/);

    if (!match) return { name: clean, unit: null };

    return { name: match[1].trim(), unit: (match[2] ?? match[3]).trim() };
}
