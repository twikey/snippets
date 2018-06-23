public class TwikeyApi {

	Http http = new Http();

	String apiToken;

	String baseUrl = 'https://api.twikey.com';

	public TwikeyApi(String apiToken){
		this.apiToken = apiToken;
	}

	// Pass in the endpoint to be used using the string url
	public String fetchSessionToken() {

		HttpRequest req = new HttpRequest();
		req.setEndpoint(baseUrl+'/creditor');
		req.setMethod('POST');
		req.setBody('apiToken=' + apiToken);

		// Send the request, and return a response
		HttpResponse res = http.send(req);
		return res.getHeader('Authorization');
	}

	public PreparedLink prepareContract(long templateId,SObject debtor) {
		String sessionToken = fetchSessionToken();
		if(sessionToken == null){
			System.debug('No session token available');
			return null;
		}

		HttpRequest req = new HttpRequest();
		req.setEndpoint(baseUrl+'/creditor/prepare');
		req.setMethod('POST');
		req.setHeader('Authorization', sessionToken);

		String body = toTwikeyBody('ct='+templateId,debtor);
		req.setBody(body);

		HTTPResponse res = http.send(req);
		if(res.getStatusCode() == 200){
			JSONParser parser = JSON.createParser(res.getBody());
			if (parser.nextToken() != null) {
				if (parser.getCurrentToken() == JSONToken.START_OBJECT) {
					PreparedLink link = (PreparedLink)parser.readValueAs(PreparedLink.class);
					system.debug('PreparedLink : ' + link.url);
					return link;
				}
			}
		}
		else {
			system.debug('Status : ' + res.getStatusCode());
			system.debug('ApiError : ' + res.getHeader('ApiError'));
			system.debug('ApiErrorCode : ' + res.getHeader('ApiErrorCode'));
		}
		return null;
	}

	public PreparedLink signContract(long templateId,String method,SObject debtor) {
		String sessionToken = fetchSessionToken();
		if(sessionToken == null){
			System.debug('No session token available');
			return null;
		}

		HttpRequest req = new HttpRequest();
		req.setEndpoint(baseUrl+'/creditor/sign');
		req.setMethod('POST');
		req.setHeader('Authorization', sessionToken);

		String body = toTwikeyBody('ct='+templateId+'&method='+method,debtor);
		req.setBody(body);

		HTTPResponse res = http.send(req);
		if(res.getStatusCode() == 200){
			JSONParser parser = JSON.createParser(res.getBody());
			if (parser.nextToken() != null) {
				if (parser.getCurrentToken() == JSONToken.START_OBJECT) {
					PreparedLink link = (PreparedLink)parser.readValueAs(PreparedLink.class);
					system.debug('PreparedLink : ' + link.url);
					return link;
				}
			}
		}
		else {
			system.debug('Status : ' + res.getStatusCode());
			system.debug('ApiError : ' + res.getHeader('ApiError'));
			system.debug('ApiErrorCode : ' + res.getHeader('ApiErrorCode'));
		}
		return null;
	}

	public Mandate fetchContract(String mndtId) {
		String sessionToken = fetchSessionToken();
		if(sessionToken == null){
			System.debug('No session token available');
			return null;
		}

		HttpRequest req = new HttpRequest();
		req.setMethod('GET');
		req.setEndpoint(baseUrl+'/creditor/mandate/detail?mndtId='+mndtId);
		req.setHeader('Authorization', sessionToken);

		HTTPResponse res = http.send(req);
		if(res.getStatusCode() == 200){
			JSONParser parser = JSON.createParser(res.getBody());
			boolean mndtInfo = false;
			while (parser.nextToken() != null) {
				if (mndtInfo && parser.getCurrentToken() == JSONToken.START_OBJECT) {
					Mandate mndt = (Mandate)parser.readValueAs(Mandate.class);
					system.debug('Mandate : ' + mndt);
					return mndt;
				}
				else {
					mndtInfo = (parser.getCurrentToken() == JSONToken.FIELD_NAME) && (parser.getText() == 'Mndt');
				}
			}
		}
		else {
			system.debug('Status : ' + res.getStatusCode());
			system.debug('ApiError : ' + res.getHeader('ApiError'));
			system.debug('ApiErrorCode : ' + res.getHeader('ApiErrorCode'));
		}
		return null;
	}

	public Long sendTransaction(String mandateNumber,String message,Decimal amount) {
		String sessionToken = fetchSessionToken();
		if(sessionToken == null){
			System.debug('No session token available');
			return null;
		}

		HttpRequest req = new HttpRequest();
		req.setEndpoint(baseUrl+'/creditor/transaction');
		req.setMethod('POST');
		req.setHeader('Authorization', sessionToken);

		String body = 'mndtId='+mandateNumber+'&message='+message+'&amount='+amount;
		req.setBody(body);

		HTTPResponse res = http.send(req);
		if(res.getStatusCode() == 200){
			JSONParser parser = JSON.createParser(res.getBody());
			while (parser.nextToken() != null) {
				if ((parser.getCurrentToken() == JSONToken.FIELD_NAME) &&
						(parser.getText() == 'id')) {
					// Get the value.
					parser.nextToken();
					// Compute the grand total price for all invoices.
					Long txId = parser.getLongValue();
					system.debug('txId : ' + txId);
					return txId;
				}
			}
		}
		else {
			system.debug('Status : ' + res.getStatusCode());
			system.debug('ApiError : ' + res.getHeader('ApiError'));
			system.debug('ApiErrorCode : ' + res.getHeader('ApiErrorCode'));
		}
		return null;
	}

	public class PreparedLink {
		public String code;
		public String url;
		public String mandateId;
	}

	public class Mandate {
		public String MndtId;
		public Debtor Dbtr;
		public Extra[] SplmtryData;
	}

	public class Debtor {
		public String Nm;
	}

	public class Extra {
		public String Key;
		public String Value;
	}

	private String toTwikeyBody(String body, SObject debtorInfo){
		// if there are too many fields or just a subset is needed, only these can be send
		for (String fieldName : debtorInfo.getPopulatedFieldsAsMap().keySet()) {
			if(fieldName == 'BillingStreet') {
				body += '&address=' + EncodingUtil.urlEncode(String.valueOf(debtorInfo.get(fieldName)), 'UTF-8');
			}
			else if(fieldName == 'BillingCity') {
				body += '&city=' + EncodingUtil.urlEncode(String.valueOf(debtorInfo.get(fieldName)), 'UTF-8');
			}
			else if(fieldName ==  'BillingPostalCode') {
				body += '&zip=' + EncodingUtil.urlEncode(String.valueOf(debtorInfo.get(fieldName)), 'UTF-8');
			}
			else if(fieldName ==  'BillingCountry') {
				body += '&country=' + EncodingUtil.urlEncode(String.valueOf(debtorInfo.get(fieldName)), 'UTF-8');
			}
			else {
				body += '&'+fieldName + '=' + EncodingUtil.urlEncode(String.valueOf(debtorInfo.get(fieldName)), 'UTF-8');
			}
		}
		system.debug('PreparedLink with data: ' + body);
		return body;
	}
}