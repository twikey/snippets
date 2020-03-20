public class WebhookServlet extends HttpServlet {

    public void service(HttpServletRequest request, HttpServletResponse response) throws IOException, ServletException{
        byte[]providedSignature=DatatypeConverter.parseHexBinary(request.getHeader("X-SIGNATURE"));
        String queryString=request.getQueryString();
        String apikey="C0EEE955DD2E2BDE3D42AB2B7EAF668C92899E1B";

        Mac mac;
        try{
            mac=Mac.getInstance("HmacSHA256");
            SecretKeySpec secret=new SecretKeySpec(apikey.getBytes(UTF_8),"HmacSHA256");
            mac.init(secret);
            byte[]calculated=mac.doFinal(queryString.getBytes(UTF_8));
            boolean equal=true;
            for(int i=0;i<calculated.length;i++){
                equal=equal&&(providedSignature[i]==calculated[i]);
            }
            System.out.println("Signature = "+equal);

        }catch(GeneralSecurityException e){
            throw new RuntimeException(e);
        }
    }
}
