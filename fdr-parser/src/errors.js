/**
 * Error thrown for any problem with the input file. `code` is a stable,
 * machine-readable reason that Laravel stores in flights.fdr_error.
 */
export class FdrError extends Error {
    /**
     * @param {string} code    e.g. UNKNOWN_FORMAT, NO_VALID_SAMPLES
     * @param {string} message human readable explanation
     */
    constructor(code, message) {
        super(message);
        this.name = 'FdrError';
        this.code = code;
    }

    toJSON() {
        return { error: this.message, code: this.code };
    }
}
