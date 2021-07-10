module.exports = {
    invoice: function(auth,invoice,purpose){
        return fetch(url+'/creditor/invoice',{
            method: 'POST',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/json",
                "Authorization": auth,
                'X-PURPOSE': 'redirect'
            },
            body: JSON.stringify(invoice)
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    invoiceReoffer: function(auth,invoiceId){
        return fetch(url+'/creditor/invoice/'+invoiceId+'/reoffer',{
            method: 'PATCH',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/json",
                "Authorization": auth
            },
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    invoiceFeed: function(auth,reset){
        let headers = {
            "User-Agent": USER_AGENT,
            "Content-Type": "application/x-www-form-urlencoded",
            "Authorization": auth
        };
        if(reset){
            headers['X-RESET'] = reset;
        }
        return fetch(url+'/creditor/invoice',{
            method: 'GET',
            headers: headers
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    invoiceDetail: function(auth,invoice){
        return fetch(url+'/creditor/invoice/'+invoice+'?include=lastpayment&include=history',{
            method: 'GET',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/json",
                "Authorization": auth
            }
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    invoiceqr: function(auth,invoiceId){
        return fetch(url+'/creditor/invoice/'+invoiceId+'/qr',{
            method: 'GET',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/json",
                "Authorization": auth
            }
        })
            .then(errorHandler)
            .then(res => res.headers.get('X-ENCODED-URL'));
    },
}
