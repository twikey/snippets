global class TwikeyMockImpl implements HttpCalloutMock {
    
    HttpResponse resp;

    global TwikeyMockImpl(HttpResponse resp){
        this.resp = resp;
    }
    
    global HTTPResponse respond(HTTPRequest req) {
        return resp;
    }
}