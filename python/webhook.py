from flask import Flask, request
from hmac import HMAC, compare_digest
from hashlib import sha256
from urllib.parse import unquote

app = Flask(__name__)

def verify_signature(req):
     received_sign = req.headers.get('X-Signature')
     if not received_sign:
         return False
     apiKey = 'A03EB2F7B251C6AD67E151CB052C4338C23D1FDE'
     payload = unquote(req.query_string)
     expected_sign = HMAC(key=apiKey.encode(), msg=payload.encode(), digestmod=sha256).hexdigest().upper()
     return compare_digest(received_sign, expected_sign)

@app.route('/webhook', methods=['GET'])
def webhook():
    if verify_signature(request):
        return 'Successfully', 200
    return 'Forbidden', 403

if __name__ == '__main__':
    #setup dev server
    app.debug = True
    app.run(host = "0.0.0.0",port=8000)
