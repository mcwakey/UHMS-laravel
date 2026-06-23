NALOPAY API DOCUMENTATION V1
Overview

NALOPAY is a secure and reliable payment API designed to facilitate seamless transactions between merchants and customers. It enables businesses to accept, process, and manage payments effortlessly.

This documentation provides comprehensive details on authentication, endpoint usage, error handling, and sample requests to help you test and integrate the API with ease.
Prerequisites

To successfully integrate with the NALOPAY API, ensure you have the following details:
REQUIREMENT	DESCRIPTION
Merchant ID	A unique identifier assigned to your merchant account.
Basic Auth token	Authentication credentials provided for your account. Must be included in the Authorization header of each request.
Secret Key	A confidential key for signing requests and generating hashes.
Endpoints
1. Generate Token

    Method: POST

    URL: {{baseURL}}/clientapi/generate-payment-token/

    Description: The first step in the collection process is to generate a payment token. This endpoint creates a JWT token for the specified merchant, which will be used in subsequent collection requests. It requires a valid merchant_id in the request body and a valid Basic Auth token in the request header.

Request Parameters
PARAMETER	DESCRIPTION
merchant_id	Your unique merchant ID

Headers
HEADER	DESCRIPTION
Authorization	Basic Auth token (Base64 encoded)
Content-Type	application/json

Sample Request Body
json

{
  "merchant_id": "1234567890"
}

Sample Response
json

{
  "success": true,
  "code": "TOKEN-CRTD-0050",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjaGFudF9pZCI6Im1ucUREaGlTc3I2WThEUHk4RDJYb0QiLCJiYXNpY19hdXRoX2hlYWRlciI6IkJhc2ljIGFjMWMzYWQxNWUxZGNhNzVjOWFlZmY2YzQ3MWNmMjc3MjQwOWRjYzE5NTRlODFiZjIwM2YzYjAzNjk3NDNjNzYwZmE1ODk5N2NmNDBmODI1ZDc1YWNmYzJhMzJlZDlkYzYzNGE4YWRlN2NhOTBlZTU2ZDdiY2IwZGY1MjZiMjViIiwiaWF0IjoxNzU1NTE4NjIyLCJleHAiOjE3NTU1MTk1MjJ9.TCn6Nlpg0LjxbiExmaHG2egkGqsWyz-dc2id8amv-Cg"
  }
}

2. Create Collection

    Method: POST

    URL: {{baseURL}}/clientapi/collection/

    Description: Creates a new payment collection for a merchant.

Headers
HEADER	DESCRIPTION
Content-Type	application/json
token	Token returned from Generate Token endpoint (JWT)

Request Fields
Field	Type	Required	Description
merchant_id	string	Yes	Your unique merchant identifier
service_name	string	Yes	"MOMO_TRANSACTION" for mobile money
trans_hash	string	Yes	HMAC SHA256 hash for verification
account_number	string	Yes	Customer's mobile money number
account_name	string	Yes	Customer's name
network	string	Yes	"MTN", "AT", or "TELECEL"
amount	number/string	Yes	Payment amount (min: 0.01)
reference	string	Yes	Your unique transaction reference
callback	string	Yes	Your webhook URL for payment updates
description	string	No	Transaction description
extra_data	object	No	Custom data to be returned in callback

How to generate trans_hash

Construct a message by concatenating the following fields in order, without separators, then compute an HMAC-SHA256 of the message using your merchant secret key.

Order of fields:

    merchant_id

    account_number

    amount

    reference

Example message (no separators):
Plain Text

MERCH123024123456750.00TXN-123

Compute: HMAC_SHA256(message, merchant_secret_key) and send the resulting hex string as trans_hash.

Sample Request Body
json

{
  "merchant_id": "hCuK9z9yoYMZ8yvtH7LUHP",
  "service_name": "MOMO_TRANSACTION",
  "trans_hash": "b004395b29a61c3df913857032b40ad0e19c3ed1b9b853d85ae2c03cc8f3e0ba",
  "account_number": "233597990630",
  "account_name": "Abdul Razak",
  "description": "description",
  "reference": "REF_2024_001",
  "callback": "http://localhost:8033",
  "network": "MTN",
  "amount": 50,
  "extra_data": {
    "key": "value"
  }
}

Sample Response
json

{
  "success": true,
  "code": "PAY-CRTD-0055",
  "data": {
    "order_id": "FPewDB25nodznJawcNykhx",
    "status": "PENDING",
    "amount": 50,
    "timestamp": "2025-08-18 12:41:23",
    "otp_code": "None*252#"
  }
}

3. Callback (Webhooks)

When a collection transaction is processed (successful or failed), the payment gateway sends a POST request to your callback URL.

    Method: POST

    URL:

    Headers:

Plain Text

Content-Type: application/json

Callback Payloads

    Successful Payment

json

{
  "order_id": "LVFf4MHD2xJ7yJ7uW9ZyMi",
  "status": "COMPLETED",
  "amount": "50.00",
  "charges": "0.00",
  "transaction_fee": "0.00"
}

    Failed Payment

json

{
  "order_id": "h7bTLkH4ZZd2m5kgd8XvGf",
  "status": "FAILED",
  "amount": "100.00",
  "charges": "0.00",
  "transaction_fee": "0.00"
}

    With Extra Data (Optional)

json

{
  "order_id": "oEgJmCznUUa89hWKrAjfCr",
  "status": "COMPLETED",
  "amount": "100.00",
  "charges": "0.00",
  "transaction_fee": "0.00",
  "extra_data": {
    "key": "value",
    "custom_field": "custom_value"
  }
}

4. Check Status

    Method: POST

    URL: {{BASE_URL}}/clientapi/collection-status/

    Description: Use this endpoint to check the status of a payment collection by providing the merchant_id and order_id. The response will indicate the current status and amount of the specified collection order.

Request Parameters
PARAMETER	DESCRIPTION
merchant_id	Your unique merchant ID
order_id	The order ID returned when the collection was created

Headers
HEADER	DESCRIPTION
Content-Type	application/json

Sample Request Body
json

{
  "merchant_id": "hCuK9z9yoYMZ8yvtH7LUHP",
  "order_id": "2jXVMbTidVjSvMGsD2fNWR"
}

Sample Response (Success)
json

{
  "success": true,
  "code": "PAY-STAT-0080",
  "data": {
    "status": "PENDING",
    "amount": 0.11
  }
}

POSTGenerate Token
{{baseURL}}clientapi/generate-payment-token/
HEADERS
Authorization

Basic 8d29d845b9e4cab596d6bed125cb10989266f36c9ec6cdbe0741b8b99c2dcca5d59d6254f9446630cb43f8bd1d9f332bf59fe285a86742955becfbd9cce4a2dc
Bodyraw (json)
json

{
    "merchant_id":"hCuK9z9yoYMZ8yvtH7LUHP"
}

Example Request
success
curl

curl --location -g '{{BASE_URL}}clientapi/generate-payment-token/' \
--header 'Authorization: Basic 184236f512e846ae689db7cf5b409c2d4a9bb4da1632e1f67609be4ba209f274b467c264e4713b6cb5434eab5d704f467b1396408416368dab574c481dc86f8c' \
--data '{
    "merchant_id":"dcvU4ihGPMHStAMKPnqksC"
}'

200 OK
Example Response

json

{
  "success": true,
  "code": "TOKEN-CRTD-0050",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjaGFudF9pZCI6Im1ucUREaGlTc3I2WThEUHk4RDJYb0QiLCJiYXNpY19hdXRoX2hlYWRlciI6IkJhc2ljIGFjMWMzYWQxNWUxZGNhNzVjOWFlZmY2YzQ3MWNmMjc3MjQwOWRjYzE5NTRlODFiZjIwM2YzYjAzNjk3NDNjNzYwZmE1ODk5N2NmNDBmODI1ZDc1YWNmYzJhMzJlZDlkYzYzNGE4YWRlN2NhOTBlZTU2ZDdiY2IwZGY1MjZiMjViIiwiaWF0IjoxNzU1NTE4NjIyLCJleHAiOjE3NTU1MTk1MjJ9.TCn6Nlpg0LjxbiExmaHG2egkGqsWyz-dc2id8amv-Cg"
  }
}

POSTCreate collection
{{baseURL}}/clientapi/collection/
HEADERS
token

eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjaGFudF9pZCI6Im1ucUREaGlTc3I2WThEUHk4RDJYb0QiLCJiYXNpY19hdXRoX2hlYWRlciI6IkJhc2ljIGFjMWMzYWQxNWUxZGNhNzVjOWFlZmY2YzQ3MWNmMjc3MjQwOWRjYzE5NTRlODFiZjIwM2YzYjAzNjk3NDNjNzYwZmE1ODk5N2NmNDBmODI1ZDc1YWNmYzJhMzJlZDlkYzYzNGE4YWRlN2NhOTBlZTU2ZDdiY2IwZGY1MjZiMjViIiwiaWF0IjoxNzU1NTIwMTA1LCJleHAiOjE3NTU1MjEwMDV9.pRARV0UXCAfEaZigbuHRnleFGWf6YDuBbJ2HUj840o0
Bodyraw (json)
json

{
    "merchant_id": "mnqDDhiSsr6Y8DPy8D2XoD",
    "service_name":"MOMO_TRANSACTION",
    "trans_hash":"69cba164a6b28202ffe9294c7977586309699c22c526f9a96ecb0f15500be7ae",
    "account_number": "0597990630",
    "account_name":"Alexander Alex",
    "description": "description",
    "reference": "REF_2024_001",
    "network": "MTN",
    "amount": 0.2
}

Example Request
sucess
curl

curl --location -g '{{BASE_URL}}clientapi/collection/' \
--header 'token: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjaGFudF9pZCI6ImhDdUs5ejl5b1lNWjh5dnRIN0xVSFAiLCJiYXNpY19hdXRoX2hlYWRlciI6IkJhc2ljIDhkMjlkODQ1YjllNGNhYjU5NmQ2YmVkMTI1Y2IxMDk4OTI2NmYzNmM5ZWM2Y2RiZTA3NDFiOGI5OWMyZGNjYTVkNTlkNjI1NGY5NDQ2NjMwY2I0M2Y4YmQxZDlmMzMyYmY1OWZlMjg1YTg2NzQyOTU1YmVjZmJkOWNjZTRhMmRjIiwiaWF0IjoxNzM5MDk3OTk3LCJleHAiOjE3MzkwOTg1OTd9.SHCybyAeNuegC5bX140x6rGATGkwBqqHXc1_B0fdRcc' \
--data '{
    "merchant_id": "mnqDDhiSsr6Y8DPy8D2XoD",
    "service_name":"MOMO_TRANSACTION",
    "trans_hash":"69cba164a6b28202ffe9294c7977586309699c22c526f9a96ecb0f15500be7ae",
    "account_number": "0244071872",
    "account_name":"Alexander Alex",
    "description": "description",
    "reference": "REF_2024_001",
    "network": "MTN",
    "amount": 0.11,
    "callback": "https://callback.nalo.com/"
}'

201 Created
Example Response

json

{
  "success": true,
  "code": "PAY-CRTD-0055",
  "data": {
    "order_id": "FPewDB25nodznJawcNykhx",
    "status": "PENDING",
    "amount": 0.11,
    "timestamp": "2025-08-18 12:41:23",
    "otp_code": "None*252#"
  }
}

POSTCheck status
{{BASE_URL}}/clientapi/collection-status/
Bodyraw (json)
json

{
    "merchant_id": "hCuK9z9yoYMZ8yvtH7LUHP",
    "order_id":"2jXVMbTidVjSvMGsD2fNWR"
}

Example Request
success
curl

curl --location -g '{{BASE_URL}}/clientapi/collection-status/' \
--data '{
    "merchant_id": "hCuK9z9yoYMZ8yvtH7LUHP",
    "order_id":"2jXVMbTidVjSvMGsD2fNWR"
}'

200 OK
Example Response

json

{
  "success": true,
  "code": "PAY-STAT-0080",
  "data": {
    "status": "PENDING",
    "amount": 0.11
  }
}