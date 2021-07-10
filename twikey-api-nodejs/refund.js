module.exports = {
    addBeneficiary: function(auth,beneficiary){
        var params = new URLSearchParams();
        for(var key in beneficiary)
            params.append(key, beneficiary[key]);
        return fetch(url+'/creditor/transfers/beneficiaries',{
            method: 'POST',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/x-www-form-urlencoded",
                "Authorization": auth
            },
            body: params
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    addRefund: function(auth,refund){
        var params = new URLSearchParams();
        for(var key in refund)
            params.append(key, refund[key]);
        return fetch(url+'/creditor/transfer',{
            method: 'POST',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/x-www-form-urlencoded",
                "Authorization": auth
            },
            body: params
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    collectRefund: function(auth,tx){
        var params = new URLSearchParams();
        for(var key in tx)
            params.append(key, tx[key]);
        return fetch(url+'/creditor/transfer/complete',{
            method: 'POST',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/x-www-form-urlencoded",
                "Authorization": auth
            },
            body: params
        })
            .then(errorHandler)
            .then(res => res.json());
    },
}
