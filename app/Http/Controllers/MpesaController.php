namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class MpesaController extends Controller
{
    public function initiateStkPush(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $phoneNumber = $request->input('phone_number');
        $amount = $request->input('amount');

        // Generate timestamp in the required format (YYYYMMDDHHMMSS)
        $timestamp = date('YmdHis');

        // Generate password (base64 encoded string of BusinessShortcode, Passkey and Timestamp)
        $plaintext = env('MPESA_SHORTCODE') . env('MPESA_PASSKEY') . $timestamp;
        $password = base64_encode($plaintext);

        // Prepare request payload
        $payload = [
            'BusinessShortCode' => env('MPESA_SHORTCODE'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phoneNumber,
            'PartyB' => env('MPESA_SHORTCODE'),
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => env('MPESA_CALLBACK_URL'),
            'AccountReference' => 'Invoice',
            'TransactionDesc' => 'Invoice Payment',
        ];

        // Send request to M-Pesa API
        $client = new Client();
        $response = $client->request('POST', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', [
            'json' => $payload,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->generateAccessToken(),
                'Content-Type' => 'application/json',
            ],
        ]);

        // Handle response from M-Pesa API
        $responseData = json_decode($response->getBody()->getContents(), true);

        // Optionally, save transaction details to your database
        // $this->saveTransaction($responseData);

        // Return response
        return response()->json($responseData);
    }

    private function generateAccessToken()
    {
        $consumerKey = env('MPESA_CONSUMER_KEY');
        $consumerSecret = env('MPESA_CONSUMER_SECRET');
        $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

        $client = new Client();
        $response = $client->request('GET', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials', [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/json',
            ],
        ]);

        $accessToken = json_decode($response->getBody()->getContents())->access_token;
        return $accessToken;
    }
}
