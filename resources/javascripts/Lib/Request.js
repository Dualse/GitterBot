import Config from "/Lib/Config";
import RequestMessage from "/Lib/Request/RequestMessage";

/**
 *
 */
export default class Request {
    /**
     * Request messages
     */
    static messages = ko.observableArray([]);

    /**
     * @type {string}
     */
    url = '';

    /**
     * @type {{}}
     */
    args = {};

    /**
     * @param url
     * @param args
     */
    constructor(url, args = {}) {
        this.url  = url;
        this.args = args;

        this.args._token = this.args._token || Config.get('csrf');
    }

    /**
     * @param title
     * @returns {*}
     */
    async get(title) {
        return await this.request(title, 'get');
    }

    /**
     * @param title
     * @returns {*}
     */
    async post(title) {
        return await this.request(title, 'post');
    }

    /**
     * @param title
     * @param method
     * @returns {Promise}
     */
    request(title, method) {
        return ((title, method) => {
            var message = new RequestMessage(title);
            Request.messages.push(message);

            return new Promise((resolve, reject) => {
                $.ajax({
                        type: method.toUpperCase(),
                        url: this.url,
                        data: this.args,
                        cache: false,
                        success: (result) => resolve(result)
                    })
                    .fail((e) => reject(e))
                    .always(() => {
                        message.visible(false);
                        setTimeout(() => {
                            Request.messages.remove(item => item.id === message.id);
                        }, 1000);
                    });
            });
        })(title, method);
    }
}
