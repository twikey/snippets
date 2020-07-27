package com.twikey;

import org.json.JSONObject;
import org.junit.Before;
import org.junit.Test;

import java.io.IOException;
import java.util.HashMap;
import java.util.Map;

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
}
