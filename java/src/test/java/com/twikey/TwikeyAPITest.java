package com.twikey;

import org.json.JSONObject;
import org.junit.Before;
import org.junit.Test;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertTrue;

public class TwikeyAPITest {

    String apiKey = "87DA7055C5D18DC5FAFC084F9F2085B335340977"; // found in https://www.twikey.com/r/admin#/c/settings/api
    long ct = 1420; // found @ https://www.twikey.com/r/admin#/c/template

    private TwikeyAPI.Customer customer;

    private TwikeyAPI api;

    @Before
    public void createCustomer(){
        customer = new TwikeyAPI.Customer()
            .setRef("customerNum123")
            .setEmail("no-reply@example.com")
            .setFirstname("Twikey")
            .setLastname("Support")
            .setStreet("Derbystraat 43")
            .setCity("Gent")
            .setZip("9000")
            .setCountry("BE")
            .setLang("nl")
            .setMobile("32498665995");

        api = new TwikeyAPI(apiKey,true);
    }

    @Test
    public void testInviteMandateWithoutCustomerDetails() throws IOException, TwikeyAPI.UserException {
        System.out.println(api.inviteMandate(ct,null,new HashMap<>()));
    }

    @Test
    public void testInviteMandateCustomerDetails() throws IOException, TwikeyAPI.UserException {
        System.out.println(api.inviteMandate(ct,customer,new HashMap<>()));
    }

    @Test
    public void testCreateInvoice() throws IOException, TwikeyAPI.UserException {
        Map<String, String> invoiceDetails = new HashMap<>();
        invoiceDetails.put("number", "Invss123");
        invoiceDetails.put("title", "Invoice April");
        invoiceDetails.put("remittance", "123456789123");
        invoiceDetails.put("amount", "10.90");
        invoiceDetails.put("date", "2020-03-20");
        invoiceDetails.put("duedate","2020-04-28");
        System.out.println(api.createInvoice(ct,customer,invoiceDetails));
    }

    @Test
    public void getMandatesAndDetails() throws IOException, TwikeyAPI.UserException {
        api.getUpdatedMandates(new TwikeyAPI.MandateCallback() {
            @Override
            public void newMandate(JSONObject newMandate) {
                System.out.println("New mandate: "+newMandate);
            }

            @Override
            public void updatedMandate(JSONObject updatedMandate) {
                System.out.println("Updated mandate: "+updatedMandate);
            }

            @Override
            public void cancelledMandate(JSONObject cancelledMandate) {
                System.out.println("Cancelled mandate: "+cancelledMandate);
            }
        },"2019-01-01");
    }

    @Test
    public void getInvoicesAndDetails() throws IOException, TwikeyAPI.UserException {
        api.getUpdatedInvoices(updatedInvoice -> System.out.println("Updated invoice: "+updatedInvoice),"2019-01-01");
    }

    @Test
    public void verifySignatureAndDecryptAccountInfo() throws IOException {
        // exiturl defined in template http://example.com?mandatenumber={{mandateNumber}}&status={{status}}&signature={{s}}&account={{account}}
        // outcome http://example.com?mandatenumber=MYDOC&status=ok&signature=8C56F94905BBC9E091CB6C4CEF4182F7E87BD94312D1DD16A61BF7C27C18F569&account=2D4727E936B5353CA89B908309686D74863521CAB32D76E8C2BDD338D3D44BBA

        String outcome = "http://example.com?mandatenumber=MYDOC&status=ok&" +
                "signature=8C56F94905BBC9E091CB6C4CEF4182F7E87BD94312D1DD16A61BF7C27C18F569&" +
                "account=2D4727E936B5353CA89B908309686D74863521CAB32D76E8C2BDD338D3D44BBA";

        String websiteKey = "BE04823F732EDB2B7F82252DDAF6DE787D647B43A66AE97B32773F77CCF12765";
        String doc = "MYDOC";
        String status = "ok";

        String signatureInOutcome = "8C56F94905BBC9E091CB6C4CEF4182F7E87BD94312D1DD16A61BF7C27C18F569";
        String encryptedAccountInOutcome = "2D4727E936B5353CA89B908309686D74863521CAB32D76E8C2BDD338D3D44BBA";
        assertTrue("Valid Signature",Utils.verifyExiturlSignature(websiteKey,doc,status,null,signatureInOutcome));
        String ibanAndBic = Utils.decryptAccountInformation(websiteKey, doc, encryptedAccountInOutcome);
        assertEquals("BE08001166979213/GEBABEBB",ibanAndBic);
    }
}
