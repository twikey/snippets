module.exports = {
    addTransaction: function(auth,tx){
        var params = new URLSearchParams();
        for(var key in tx)
            params.append(key, tx[key]);
        return fetch(url+'/creditor/transaction',{
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
    removeTransaction: function(auth,id,ref){
        var params = new URLSearchParams();
        if(id)
            params.append("id", id);
        else if(ref)
            params.append("ref", ref);
        else
            throw "No id not ref";
        return fetch(url+'/creditor/transaction?'+params.toString(),{
            method: 'DELETE',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/x-www-form-urlencoded",
                "Authorization": auth
            }
        })
            .then(errorHandler)
            .then(res => res.status === 204);
    },
    transactionFeed: function(auth,reset){
        let headers = {
            "User-Agent": USER_AGENT,
            "Content-Type": "application/x-www-form-urlencoded",
            "Authorization": auth
        };
        if(reset){
            headers['X-RESET'] = reset;
        }
        return fetch(url+'/creditor/transaction?include=lastupdate&include=action&include=collection&include=seq',{
            method: 'GET',
            headers: headers
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    transactionDetails: function(auth,reset,items){
        if(!items.id || !items.ref || !items.mndtId){
            throw "Required is id/ref or mndtId";
        }
        var params = new URLSearchParams();
        for(var key in items)
            params.append(key, items[key]);
        let headers = {
            "User-Agent": USER_AGENT,
            "Content-Type": "application/x-www-form-urlencoded",
            "Authorization": auth
        };
        return fetch(url+'/creditor/transaction/detail?'+params.toString(),{
            method: 'GET',
            headers: headers
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    collect: function(auth,templateOrExtra){
        var params = new URLSearchParams();
        for(var key in templateOrExtra)
            params.append(key, templateOrExtra[key]);
        return fetch(url+'/creditor/collect',{
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
    }
}
