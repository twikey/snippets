package com.twikey;

import javax.crypto.Cipher;
import javax.crypto.Mac;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;
import javax.xml.bind.DatatypeConverter;
import java.security.GeneralSecurityException;
import java.security.MessageDigest;

import static java.nio.charset.StandardCharsets.UTF_8;
import static javax.xml.bind.DatatypeConverter.parseHexBinary;

public class Utils {

    /**
     * @param apikey API key
     * @param signatureHeader request.getHeader("X-SIGNATURE")
     * @param queryString request.getQueryString()
     */
    public static void verifyWebHookSignature(String apikey, String signatureHeader, String queryString){
        byte[] providedSignature = DatatypeConverter.parseHexBinary(signatureHeader);

        Mac mac;
        try {
            mac = Mac.getInstance("HmacSHA256");
            SecretKeySpec secret = new SecretKeySpec(apikey.getBytes(UTF_8), "HmacSHA256");
            mac.init(secret);
            byte[] calculated = mac.doFinal(queryString.getBytes(UTF_8));
            boolean equal = true;
            for (int i = 0; i < calculated.length; i++) {
                equal = equal && (providedSignature[i] == calculated[i]);
            }
            System.out.println("Signature = " + equal);

        } catch (GeneralSecurityException e) {
            throw new RuntimeException(e);
        }
    }

    /**
     * @param websitekey Provided in Settings > Website
     * @param document Mandatenumber or other
     * @param status Outcome of the request
     * @param token If provided in the initial request
     * @param signature Given in the exit url
     * @return whether or not the signature is valid
     */
    public static boolean verifyExiturlSignature(String websitekey, String document,String status,String token,String signature){
        byte[] providedSignature = DatatypeConverter.parseHexBinary(signature);

        Mac mac;
        try {
            mac = Mac.getInstance("HmacSHA256");
            SecretKeySpec secret = new SecretKeySpec(websitekey.getBytes(UTF_8), "HmacSHA256");
            mac.init(secret);
            String payload = document+"/"+status;
            if(token != null) {
                payload += "/"+token;
            }
            byte[] calculated = mac.doFinal(payload.getBytes(UTF_8));
            boolean equal = true;
            for (int i = 0; i < calculated.length; i++) {
                equal = equal && (providedSignature[i] == calculated[i]);
            }
            return equal;
        } catch (GeneralSecurityException e) {
            throw new RuntimeException(e);
        }
    }

    /**
     * @param websitekey Provided in Settings > Website
     * @param document Mandatenumber or other
     * @param encryptedAccount encrypted account info
     */
    public static String decryptAccountInformation(String websitekey, String document,String encryptedAccount) {
        String key = document+websitekey;
        try {
            byte[] keyBytes = MessageDigest.getInstance("MD5").digest(key.getBytes(UTF_8));
            Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
            cipher.init(Cipher.DECRYPT_MODE, new SecretKeySpec(keyBytes, "AES"),new IvParameterSpec(keyBytes));
            byte[] val = cipher.doFinal(DatatypeConverter.parseHexBinary(encryptedAccount));
            return new String(val,UTF_8);
        } catch (Exception e) {
            throw new RuntimeException("Exception decrypting : " + encryptedAccount, e);
        }
    }

    /**
     * For use when enhanced security on the API is required
     */
    public static long generateOtp(String salt, String privateKey) throws GeneralSecurityException {
        if (privateKey == null)
            throw new IllegalArgumentException("Invalid key");

        long counter = (long) Math.floor(System.currentTimeMillis() / 30000);
        byte[] key = parseHexBinary(privateKey);

        if (salt != null) {
            byte[] saltBytes = salt.getBytes(UTF_8);
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
