# Lookup Account

Returns plaintext or json data of accounts matching either an LPA ID or a user's email address.

```bash
aws-vault exec identity -- python ./lookup_account.py --environment demo --lpa_id 700000002258

email@example.com
Activation Status: Activated Last Login: 2020-06-11T08:23:14+00:00
LPAs: [  {
    "700000002258": "2020-04-20T09:37:36.934845Z",
    "700000002175": "2020-04-20T09:38:16.875585Z"
  }
]

email1@example.com
Activation Status: Activated
Last Login: 2020-05-11T09:48:13+00:00
LPAs: [
  {
    "700000002258": "2020-02-06T10:52:15.557214Z",
    "700000002191": "2020-02-17T13:46:09.382055Z",
    "700000048798": "2020-04-01T10:42:17.795033Z"
  }
]

email2@example.com
Activation Status: Activated
Last Login: 2020-03-18T10:25:37+00:00
LPAs: [
  {
    "700000002175": "2020-02-13T11:23:50.463216Z",
    "700000002258": "2020-02-13T10:59:31.377210Z",
    "700000002191": "2020-02-13T11:22:23.122868Z"
  }
]
```

```bash
aws-vault exec identity -- python ./lookup_account.py --environment demo --email_address email@example.com

email@example.com
Activation Status: Activated
Last Login: 2020-06-11T08:23:14+00:00
LPAs: [
  {
    "700000002258": "2020-04-20T09:37:36.934845Z",
    "700000002175": "2020-04-20T09:38:16.875585Z"
  }
]
```

Arguments can be used to specify json output.

```bash
aws-vault exec identity -- python ./lookup_account.py -h

usage: lookup_account.py [-h] [--environment ENVIRONMENT] [--email_address EMAIL_ADDRESS]
                         [--lpa_id LPA_ID] [--json]

Look up an account by email address.

optional arguments:
  -h, --help            show this help message and exit
  --environment ENVIRONMENT
                        The environment to target, defaults to production.
  --email_address EMAIL_ADDRESS
                        Email address to look up
  --lpa_id LPA_ID       Sirius LPA ID to look up
  --json                Output json data instead of plaintext to terminal
```
