import java.io.*;
import java.lang.String;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.ExecutionException;

import static javax.xml.bind.DatatypeConverter.parseHexBinary;

public class TwikeyAPI {

    public static final String UTF_8 = "UTF-8";

    //from_creditor_env
    private static final String TEMPLATE_ID = "1";// id of contract template in https://www.twikey.com/r/admin#/c/template
    private static final String API_TOKEN = "API_TOKEN"; // found in https://www.twikey.com/r/admin#/c/settings/ei

    public static void main(String... args) throws GeneralSecurityException, ExecutionException, InterruptedException, IOException {

        String query = String.format("apiToken=%s", API_TOKEN);

        URL myurl;
        HttpURLConnection con;
        DataOutputStream output;
        String sessionToken;

        // login
        {
            myurl = new URL("https://api.twikey.com/creditor");
            con = (HttpURLConnection)myurl.openConnection();
            con.setRequestMethod("POST");
            con.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
            con.setDoOutput(true);
            con.setDoInput(true);

            output = new DataOutputStream(con.getOutputStream());
            output.writeBytes(query);
            output.close();

            sessionToken = con.getHeaderField("Authorization");
            con.disconnect();
            System.out.println("Authorization:" + sessionToken);
        }

// prepare new contract and redirect user
        if(sessionToken != null){
            Map&lt;String,String&gt; params = new HashMap&lt;&gt;();
            params.put("ct",TEMPLATE_ID);
            params.put("email","info@twikey.com");
            params.put("firstname","Info");
            params.put("lastname","Twikey");
            params.put("l","en");
            params.put("address","Abbey road");
            params.put("city","Liverpool");
            params.put("zip","1526");
            params.put("country","BE");
            params.put("mobile","");
            params.put("companyName","");
            params.put("form","");
            params.put("vatno","");
            params.put("iban","");
            params.put("bic","");
            params.put("mandateNumber","");
            params.put("contractNumber","");
            params.put("es","");


            myurl = new URL("https://api.twikey.com/creditor/prepare");
            con = (HttpURLConnection)myurl.openConnection();
            con.setRequestMethod("POST");
            con.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");
            con.setRequestProperty("Authorization", sessionToken);
            con.setDoOutput(true);
            con.setDoInput(true);

            output = new DataOutputStream(con.getOutputStream());
            output.writeBytes(getPostDataString(params));
            output.flush();
            output.close();

            int responseCode=con.getResponseCode();

            StringBuilder response = new StringBuilder();
            if (responseCode == HttpURLConnection.HTTP_OK) {
                String line;
                BufferedReader br=new BufferedReader(new InputStreamReader(con.getInputStream()));
                while ((line=br.readLine()) != null) {
                    response.append(line);
                }
            }

            con.disconnect();
            System.out.println("Url to redirect too:" + response);
        }
    }

    private static String getPostDataString(Map&lt;String, String&gt; params) throws UnsupportedEncodingException {
        StringBuilder result = new StringBuilder();
        boolean first = true;
        for(Map.Entry&lt;String, String&gt; entry : params.entrySet()){
            if (first)
                first = false;
            else
                result.append("&");

            result.append(URLEncoder.encode(entry.getKey(), UTF_8));
            result.append("=");
            result.append(URLEncoder.encode(entry.getValue(), UTF_8));
        }

        return result.toString();
    }

    public static long generateOtp(String salt, String _key, long counter) throws GeneralSecurityException {
        if (_key == null) throw new IllegalArgumentException("Invalid key");

        byte[] key = parseHexBinary(_key);

        if (salt != null) {
            byte[] saltBytes = salt.getBytes(StandardCharsets.UTF_8);
            byte[] key2 = new byte[saltBytes.length+key.length];
            System.arraycopy(saltBytes, 0, key2, 0, saltBytes.length);
            System.arraycopy(key, 0, key2, saltBytes.length , key.length);
            key = key2;
        }

        Mac mac = Mac.getInstance("HmacSHA256");
        mac.init(new SecretKeySpec(key, "SHA256"));

        // get the bytes from the int
        byte[] counterAsBytes = new byte[8];
        for (int i = 7; i >= 0; --i) {
            counterAsBytes[i] = (byte) (counter & 255);
            counter = counter >> 8;
        }

        byte[] hash = mac.doFinal(counterAsBytes);
        int offset = hash[19] & 0xf;
        long v = (hash[offset] & 0x7f) << 24 |
                (hash[offset + 1] & 0xff) << 16 |
                (hash[offset + 2] & 0xff) << 8 |
                (hash[offset + 3] & 0xff);
        // last 8 digits are important
        return v % 100000000;
    }
}