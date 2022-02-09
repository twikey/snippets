module.exports = {
    invite: function (auth, entity) {
        var params = new URLSearchParams();
        for (var key in entity)
            params.append(key, entity[key]);
        return fetch(url + '/creditor/invite', {
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
    sign: function (auth, entity) {
        var params = new URLSearchParams();
        for (var key in entity)
            params.append(key, entity[key]);

        return fetch(url + '/creditor/sign', {
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
    update: function(auth,items){
        if(!items || !items.mndtId)
            throw "mndtId is missing";

        var params = new URLSearchParams();
        for(var key in items)
            params.append(key, items[key]);
        return fetch(url+'/creditor/mandate/update',{
            method: 'POST',
            headers: {
                "User-Agent": USER_AGENT,
                "Content-Type": "application/x-www-form-urlencoded",
                "Authorization": auth
            },
            body: params
        })
            .then(errorHandler);
    },
    feed: function(auth){
        let headers = {
            "User-Agent": USER_AGENT,
            "Content-Type": "application/x-www-form-urlencoded",
            "Authorization": auth
        };
        return fetch(url+'/creditor/mandate',{
            method: 'GET',
            headers: headers
        })
            .then(errorHandler)
            .then(res => res.json());
    },
    detail: function(auth,mndtId){
        let headers = {
            "User-Agent": USER_AGENT,
            "Content-Type": "application/x-www-form-urlencoded",
            "Authorization": auth
        };
        return fetch(url+'/creditor/mandate/detail?mndtId='+mndtId,{
            method: 'GET',
            headers: headers
        })
        .then(errorHandler)
        .then(res => res.json());
    }
}
