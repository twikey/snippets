@isTest
private class TwikeyApiTest {
	@IsTest 
	static void myTest() {		
		HttpResponse res = new HttpResponse();
		res.setHeader('Authorization', '123');
		res.setStatusCode(200);
		TwikeyMockImpl tmi = new TwikeyMockImpl(res);
		Test.setMock(HttpCalloutMock.class, tmi);
		
		Long templateId = 338;
		String privateKey = 'FE1D75B2E261BBA714DF0517760E5F03A6688';
		TwikeyApi api = new TwikeyApi(privateKey);
		Account account = new Account(
			name='Twikey',
			BillingStreet='derbystraat',
			BillingCity='Gent',
			BillingPostalCode='9051',
			BillingCountry='BE'
		);
		// prepare a contract so the url can be sent in an email
		api.prepareContract(templateId,account);
		// or invite a debtor to sign via sms
		api.signContract(templateId,'sms',account);
		// upon callback or through a cron schedule fetch the mandateInformation to store in the Account or as a Contract
		api.fetchContract('TEST1');
		// send a transaction when an invoice is generated for collection
		api.sendTransaction('SalesForce1','Invoice 123',2.99);			
	}
}