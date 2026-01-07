export class Api {
    static cache = {};

    static async request({ endpoint, method = "GET", data = null, raw = false, cache = false }) {
        // normalize, determine url
        method = method.toUpperCase();
        if (endpoint.charAt(0) == "/") endpoint = endpoint.substr(1);
        const url = `/api/v2/${endpoint}`;

        // check cache
        let cacheKey = false;
        if (cache) {
            cacheKey = `${method} ${endpoint}`;

            if (this.cache[cacheKey]) {
                return this.cache[cacheKey];
            }
        }

        const headers = {
            "X-Auth-ID": readCookie("ob_auth_id"),
            "X-Auth-Key": readCookie("ob_auth_key"),
        };

        if (data) {
            headers["Content-Type"] = "application/json";
            data = JSON.stringify(data);
        }

        const fetchArgs = {
            method,
            headers,
            body: data,
        };

        const responseHandler = async (response) => {
            if (!response.ok) {
                return false;
            }

            if (raw) {
                const blob = await response.blob();
                return blob;
            } else {
                const responseData = await response.json();
                return responseData?.data;
            }
        };

        if (cacheKey) {
            this.cache[cacheKey] = fetch(url, fetchArgs).then(responseHandler);
            return this.cache[cacheKey];
        } else {
            return await fetch(url, fetchArgs).then(responseHandler);
        }
    }
}
