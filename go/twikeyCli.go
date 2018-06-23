package main

import (
	"bytes"
	"encoding/base64"
	"crypto/hmac"
	"crypto/sha256"
	"encoding/binary"
	"encoding/hex"
	"encoding/json"
	"flag"
	"fmt"
	ini "github.com/vaughan0/go-ini"
	"github.com/peterh/liner"
	"io/ioutil"
	"math"
	"net/http"
	"net/url"
	"os"
	"bufio"
	"strings"
	"time"
	"sort"
	"strconv"
)

const (
	otpdebug = false
	JSON = "application/json"
	XML = "application/xml"
)

var (
	debug = false
	mimeType = JSON
	authToken = ""
	lang = "en"
	names = []string{"actionBank", "mandate", "cancel", "bill", "billing", "collect", "payment", "campaign"}
	commands = map[string]string{
		"mandate":                                                  "Retrieve mandates",
		"mandateActive":                                            "Retrieve mandates (incl active events)",
		"upload":                                                   "Upload mandates",
		"sign <key> <type> <payload>":                              "Sign payload using type",
		"mandateSince <epoch>":                                     "Retrieve mandates since epoch",
		"pdf":                                                      "Retrieve pdf",
		"cancel <mndtId> <reason>":                                 "Cancel mandate",
		"actionBank <creditorId> <mndtId> <action> [<reason>]":     "Action mandate",
		"transfer <iban> <amount> <msg>":                           "CT a certain amount on a mandate to collect afterwards",
		"transferdone <ct>":                           	            "prepare the creditTransfer for sending",
		"tx <mndtId> <amount> <msg>":                               "Put a certain amount on a mandate to collect afterwards",
		"txcollect <mndtId> <collectdate> <amount> <msg>":          "Put a certain amount on a mandate to collect on a date",
		"tx":                                                       "Retrieve all current amounts to be collected",
		"collect <ct>":                                             "Make a collection for a certain contract template",
		"collectSdd <ct> <future>":                                 "Make a collection returning sdd for a certain contract template",
        	"collectNotif <ct>":                                        "Make a collection for a certain contract template",
		"collectMessage <ct> <json>":                               "Make a collection for a certain contract template where json overrides messages",
		"payment":                                                  "Find out the status about current payments",
		"payment <id>":                                             "Find out the status about a specific payment",
		"update":                                                   "Update an amendment",
		"paymentSince <epoch>":                                     "Find out the status about current payments",
		"campaign":                                                 "View the status about shorteners in a campaign",
		"legal":                                                    "View the legal texts",
		"files":                                                    "View all camt files",
		"file":                                                     "Fetch camt file",
	}
)

func generateOtp(_salt string, _privKey string) (int, error) {

	if _privKey == "" {
		return 0,nil
	}

	salt := []byte(_salt)
	privkey, err := hex.DecodeString(_privKey)

	if err != nil {
		return 0, err
	}

	total := len(salt) + len(privkey)
	key := make([]byte, total, total)
	copy(key, salt)
	copy(key[len(salt):], privkey)

	if otpdebug {
		fmt.Println("Salt: ", hex.EncodeToString(salt))
		fmt.Println("Priv Key: ", hex.EncodeToString(privkey))
		fmt.Println("Combined Key: ", hex.EncodeToString(key))
	}

	buf := make([]byte, 8)
	_time := time.Now().UTC().Unix() //*1000
	counter := uint64(math.Floor(float64(_time / 30)))
	binary.BigEndian.PutUint64(buf, counter)

	if otpdebug {
		fmt.Println("HMac counter: ", counter)
		fmt.Println("HMac input: ", hex.EncodeToString(buf))
	}

	mac := hmac.New(sha256.New, key)
	mac.Write(buf)
	hash := mac.Sum(nil)

	if otpdebug {
		fmt.Println("HMac output: ", hex.EncodeToString(hash))
	}

	offset := hash[19] & 0xf
	v := int64(((int(hash[offset]) & 0x7f) << 24) |
	((int(hash[offset + 1] & 0xff)) << 16) |
	((int(hash[offset + 2] & 0xff)) << 8) |
	(int(hash[offset + 3]) & 0xff))

	// last 8 digits are important
	if otpdebug {
		fmt.Println("OTP: ", (v % 100000000))
	}

	return int(v % 100000000), nil
}

func login(client *http.Client, salt string, apiToken string, privKey string, baseUrl string) error {

	otp, _ := generateOtp(salt, privKey)
	params := url.Values{}
	params.Add("apiToken", apiToken)
	params.Add("otp", fmt.Sprint(otp))

	req, err := http.NewRequest("POST", baseUrl, strings.NewReader(params.Encode()))
	req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
	if err != nil {
		return err
	}

	resp, err := client.Do(req)
	token := resp.Header["Authorization"]
	if resp.StatusCode == 200 && token != nil {
		fmt.Printf("Connected %s\n", token)
		authToken = token[0]
	}
	return nil
}

func handleResponse(res *http.Response, file string) string {
	errorCode := res.Header["Apierrorcode"]
	error := res.Header["Apierror"]
	if error == nil {
		error = make([]string, 1)
	}
	if res.StatusCode == 200 {
		if debug {
			fmt.Printf("< Response: %d \n", res.StatusCode)
			for key, val := range res.Header {
				fmt.Printf("< %s: %s \n", key, val[0])
			}
			fmt.Println("")
		}
		if errorCode != nil {
			errmsg := errorCode[0] + " : " + error[0]
			return errmsg
		} else {
			payload, _ := ioutil.ReadAll(res.Body)
			res.Body.Close()
			if file == "" {
				if mimeType == JSON {
                    var prettyJSON bytes.Buffer
                    json.Indent(&prettyJSON, payload, "", "    ")
                    fmt.Printf("%s \n", prettyJSON.String())
                } else {
                    fmt.Printf("%s \n", payload)
                }
			} else {
				f, _ := os.Create(file)
				defer f.Close()
				f.Write(payload)
			}
		}
	} else {
		if debug {
			fmt.Printf("< Response: %d \n", res.StatusCode)
			for key, val := range res.Header {
				fmt.Printf("< %s: %s \n", key, val[0])
			}
			payload, _ := ioutil.ReadAll(res.Body)
			res.Body.Close()
			if mimeType == JSON {
                var prettyJSON bytes.Buffer
                json.Indent(&prettyJSON, payload, "", "    ")
                fmt.Printf("%s \n", prettyJSON.String())
			} else {
			    fmt.Printf("%s \n", payload)
			}
		}
		if errorCode != nil {
			errmsg := errorCode[0] + " : " + error[0]
			return errmsg
		}
	}
	return ""
}

func handleCommand(client *http.Client, baseUrl string,chunksize string, cmd string) string {

    cmd = strings.Trim(cmd, " ")
    if cmd == "" {
        return ""
    } else if cmd == "en" {
        lang = "en"
        return "Using en"
    } else if cmd == "fr" {
        lang = "fr"
        return "Using fr"
    } else if cmd == "nl" {
        lang = "nl"
        return "Using nl"
    } else if cmd == "xml" {
        mimeType = XML
        return "Using xml"
    } else if cmd == "json" {
        mimeType = JSON
        return "Using json"
    } else if cmd == "debug" {
        debug = !debug
        if debug {
            return "Debug enabled"
        } else {
            return "Debug disabled"
        }
    } else if cmd == "mandate" {
		req, _ := http.NewRequest("GET", baseUrl + "/mandate?chunkSize=" + chunksize, nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")
	} else if cmd == "mandateActive" {

		req, _ := http.NewRequest("GET", baseUrl + "/mandate?chunkSize=" + chunksize, nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("X-Activation-Events", "true")
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")
	} else if cmd == "mandateWithCoda" {

		req, _ := http.NewRequest("GET", baseUrl + "/mandate?chunkSize=" + chunksize, nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("TYPES", "CORE,B2B,CODA")
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")
	} else if strings.HasPrefix(cmd, "mandateSince ") {
		parts := strings.Split(cmd, " ")

		msInt, _ := strconv.ParseInt(parts[1], 10, 64)
		t := time.Unix(0, msInt * int64(time.Millisecond))

		req, _ := http.NewRequest("GET", baseUrl + "/mandate?chunkSize=" + chunksize, nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("RESET", t.Format(time.RFC3339))
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")
	} else if strings.HasPrefix(cmd, "actionBank ") {
		parts := strings.Split(cmd, " ")

		action := parts[3]
		params := url.Values{}
		params.Add("creditorId", parts[1])
		params.Add("mndtId", parts[2])
		if len(parts) > 3 {
			params.Add("rsn", strings.Join(parts[4:], " "))
		}

		req, _ := http.NewRequest("POST", baseUrl + "/mandate/" + action, strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "cancel ") {
		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("mndtId", parts[1])
		params.Add("rsn", strings.Join(parts[2:], " "))

		req, _ := http.NewRequest("DELETE", baseUrl + "/mandate?" + params.Encode(), nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "update ") {
		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("mndtId", parts[1])

		// loop over parts[2:]
		for _, kv := range parts[2:] {
			keyVal := strings.Split(kv, "=")
			params.Add(keyVal[0], keyVal[1])
		}

		req, _ := http.NewRequest("POST", baseUrl + "/mandate/update", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "pdf ") {
		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("mndtId", parts[1])

		req, _ := http.NewRequest("GET", baseUrl + "/mandate/pdf?" + params.Encode(), nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)

		downloadFile := parts[1] + ".pdf"
		fmt.Println("Saving to file:", downloadFile)
		return handleResponse(res, downloadFile)

	} else if strings.HasPrefix(cmd, "upload") {
		parts := strings.Split(cmd, " ")

		if _, err := os.Stat(parts[1]); err == nil {
			file, err := os.Open(parts[1]) // For read access.
			if err != nil {
				fmt.Println("Error reading file: ", err)
			}

			req, _ := http.NewRequest("POST", baseUrl + "/mandate", file)
			req.Header.Add("Accept-Language", lang)
			req.Header.Add("Authorization", authToken)
			req.Header.Add("Accept", mimeType)

			res, _ := client.Do(req)
			return handleResponse(res, "")
		} else {
			fmt.Println("Error finding file: ", err)
		}
	} else if strings.HasPrefix(cmd, "sign ") {
		parts := strings.Split(cmd, " ")

		if _, err := os.Stat(parts[3]); err == nil {
			imgFile, err := os.Open(parts[3]) // For read access.
			if err != nil {
				fmt.Println("Error reading file: ", err)
			}
			defer imgFile.Close()

			// create a new buffer base on file size
			fInfo, _ := imgFile.Stat()
			var size = fInfo.Size()
			buf := make([]byte, size)

			// read file content into buffer
			fReader := bufio.NewReader(imgFile)
			fReader.Read(buf)

			// convert the buffer bytes to base64 string - use buf.Bytes() for new image
			imgBase64Str := base64.StdEncoding.EncodeToString(buf)

			params := url.Values{}
			params.Add("key", parts[1])
			params.Add("method", parts[2])
			params.Add("selfie", imgBase64Str)

			req, _ := http.NewRequest("POST", baseUrl + "/sign", strings.NewReader(params.Encode()))
			req.Header.Add("Accept-Language", lang)
			req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
			req.Header.Add("Authorization", authToken)
			req.Header.Add("Accept", mimeType)

			res, _ := client.Do(req)
			return handleResponse(res, "")
		} else {
			fmt.Println("Error finding file: ", err)
		}

	} else if strings.HasPrefix(cmd, "txcollect ") {
		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("mndtId", parts[1])
		params.Add("reqcolldt", parts[2])
		params.Add("amount", parts[3])
		params.Add("message", strings.Join(parts[4:], " "))

		req, _ := http.NewRequest("POST", baseUrl + "/transaction", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "tx ") {
		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("mndtId", parts[1])
		params.Add("amount", parts[2])
		params.Add("message", strings.Join(parts[3:], " "))

		req, _ := http.NewRequest("POST", baseUrl + "/transaction", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "transfer ") {
		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("iban", parts[1])
		params.Add("amount", parts[2])
		params.Add("message", strings.Join(parts[3:], " "))

		req, _ := http.NewRequest("POST", baseUrl + "/transfer", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "transferdone ") {
		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("ct", parts[1])

		req, _ := http.NewRequest("POST", baseUrl + "/transfer/complete", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if cmd == "tx" {
		req, _ := http.NewRequest("GET", baseUrl + "/transaction?chunkSize=40", nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if cmd == "files" {
		req, _ := http.NewRequest("GET", baseUrl + "/files", nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "file ") {

		parts := strings.Split(cmd, " ")

		req, _ := http.NewRequest("GET", baseUrl + "/files?file=" + parts[1], nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)

		downloadFile := parts[1] + ".camt"
		fmt.Println("Saving to file:", downloadFile)
		return handleResponse(res, downloadFile)

	} else if strings.HasPrefix(cmd, "legal ") {

		parts := strings.Split(cmd, " ")

		req, _ := http.NewRequest("GET", baseUrl + "/legal?locale=" + parts[1], nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "collectMessage ") {

		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("ct", parts[1])
		params.Add("mndtAndMsgs", parts[2])

		req, _ := http.NewRequest("POST", baseUrl + "/collect", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "collectNotif ") {

		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("ct", parts[1])
		params.Add("prenotify", "1")

		req, _ := http.NewRequest("POST", baseUrl + "/collect", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "collect ") {

		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("ct", parts[1])

		req, _ := http.NewRequest("POST", baseUrl + "/collect", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "collectSdd ") {

		parts := strings.Split(cmd, " ")

		params := url.Values{}
		params.Add("ct", parts[1])
		params.Add("future", parts[2])

		req, _ := http.NewRequest("POST", baseUrl + "/collect/sdd", strings.NewReader(params.Encode()))
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		ret := handleResponse(res, "sdd.zip")
		fmt.Println("Written to sdd.zip")
		return ret
	} else if strings.HasPrefix(cmd, "payment ") {

		parts := strings.Split(cmd, " ")

		req, _ := http.NewRequest("GET", baseUrl + "/payment?detail=true&id=" + parts[1], nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if cmd == "payment" {

		req, _ := http.NewRequest("GET", baseUrl + "/payment?detail=true", nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if strings.HasPrefix(cmd, "paymentSince ") {

		parts := strings.Split(cmd, " ")

		msInt, _ := strconv.ParseInt(parts[1], 10, 64)
		t := time.Unix(0, msInt * int64(time.Millisecond))

		req, _ := http.NewRequest("GET", baseUrl + "/payment?detail=true", nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("RESET", t.Format(time.RFC3339))
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")

	} else if cmd == "campaign" {
		req, _ := http.NewRequest("GET", baseUrl + "/campaign", nil)
		req.Header.Add("Accept-Language", lang)
		req.Header.Add("Content-Type", "application/x-www-form-urlencoded")
		req.Header.Add("Authorization", authToken)
		req.Header.Add("Accept", mimeType)

		res, _ := client.Do(req)
		return handleResponse(res, "")
	} else {
		fmt.Printf("Unknown command '%s', type \\h to see available commands\n", cmd)
	}
	return ""
}

func main() {

	env := ""
	apiToken := ""
	privkey := ""
	file := ""
	chunksize := "99"
	salt := os.Getenv("TWIKEY_SALT")
	if salt == "" {
		salt = "own"
	}
	if os.Getenv("DEBUG") != "" {
	    debug = true
	}
	baseUrl := os.Getenv("TWIKEY_URL")
	if baseUrl == "" {
		baseUrl = "https://api.twikey.com" //"http://www.twikey/api/creditor"
	}

	if len(os.Args) == 2 {
		env = os.Args[1]
	} else {
		flag.StringVar(&env, "env", os.Getenv("TWIKEY_ENV"), "Environment to use provided twikey.ini is available. Defaults to $TWIKEY_ENV")
		flag.StringVar(&salt, "salt", salt, "Salt to use the api. Defaults to $TWIKEY_SALT")
		flag.StringVar(&apiToken, "apitoken", os.Getenv("TWIKEY_TOKEN"), "Token to authenticate with. Defaults to $TWIKEY_TOKEN")
		flag.StringVar(&privkey, "privkey", os.Getenv("TWIKEY_KEY"), "Key to calculate the otp to authenticate with. Defaults to $TWIKEY_KEY")
		flag.StringVar(&baseUrl, "url", baseUrl, "Url to connecto to. Defaults to $TWIKEY_URL")
		flag.StringVar(&file, "file", file, "File to parse")
		flag.Parse()
	}

	if env != "" {
		file, err := ini.LoadFile("twikey.ini")
		if err == nil {
			if file[env] != nil {
				fmt.Printf("Config file using '%s'\n", env)
				apiToken, _ = file.Get(env, "apiToken")
				privkey, _ = file.Get(env, "privateKey")
				baseUrl, _ = file.Get(env, "url")
				chunksize, _ = file.Get(env, "chunksize")
			} else {
				fmt.Printf("Environment '%s' not found, available :\n\t", env)
				for name, _ := range file {
					fmt.Print(name," ")
				}
                		fmt.Print("\n")
				os.Exit(-3)
			}
		} else {
			fmt.Println("Config file 'twikey.ini' not found (and env-param was set)", env)
			os.Exit(-2)
		}
	}

	if apiToken == "" && privkey == "" {
	    fmt.Println("No apiToken / privKey found")
		flag.PrintDefaults()
		os.Exit(-1)
	}

	client := &http.Client{}
	if login(client, salt, apiToken, privkey, baseUrl) == nil {

        if file != "" {

            _file, err := os.Open(file)
            if err != nil {
                fmt.Println(err)
                os.Exit(-3)
            }
            defer _file.Close()

            scanner := bufio.NewScanner(_file)
            for scanner.Scan() {
                handleCommand(client,baseUrl,chunksize,scanner.Text())
            }

            if err := scanner.Err(); err != nil {
                fmt.Println(err)
                os.Exit(-3)
            }

		} else {
            line := liner.NewLiner()
            line.SetCtrlCAborts(true)
            line.SetCompleter(func(line string) (c []string) {
                for _, n := range names {
                    if strings.HasPrefix(n, strings.ToLower(line)) {
                        c = append(c, n)
                    }
                }
                return
            })
            defer line.Close()

            for {
                if cmd, err := line.Prompt("Twikey > "); err != nil {
                    fmt.Print("Error reading line: ", err)
                } else {
                    if cmd == "q" {
                        break
                    } else if cmd == "history" {
                        line.WriteHistory(os.Stdout)
                    } else if cmd == "h" {
                        fmt.Print("\tTwikey Help\n")
                        fmt.Print("\t===========\n")

                        var keys []string
                        for k := range commands {
                            keys = append(keys, k)
                        }
                        sort.Strings(keys)

                        for _, key := range keys {
                            fmt.Printf("\t%s : %s\n", key, commands[key])
                        }

                        fmt.Print("\n\thistory : history\n")
                        fmt.Print("\th : help\n")
                        fmt.Print("\tq : quit\n")
                    } else {
                        cmdErr := handleCommand(client,baseUrl,chunksize,cmd)
                        if cmdErr == "401 : " {
                            if login(client, salt, apiToken, privkey, baseUrl) != nil {
                                break
                            } else {
                                fmt.Print("Failed login, verify token/private key\n")
                                os.Exit(-3)
                            }
                        } else if cmdErr != "" {
                            fmt.Printf("Error %s\n", cmdErr)
                        }
                        line.AppendHistory(cmd)
                    }
                }
            }
		}
	}
}
