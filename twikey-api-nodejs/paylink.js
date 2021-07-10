module.exports = {
    paylink: function(auth, entity){
        var params = new URLSearchParams();
        for(var key in entity)
            params.append(key, entity[key]);

        return fetch(url+'/creditor/payment/link',{
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
