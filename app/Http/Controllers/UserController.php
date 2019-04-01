<?php
namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{

    /*
    *   The authenticate method attempts to log a user in and
    *   generates an authorization token if the user is found in the database.
    *   It throws an error if the user is not found or if an exception
    *   occurred while trying to find the user.
    */
    public function authenticate(Request $request)
    {
        $credentials = $request->only('username', 'password');

        try {
          if (! $token = JWTAuth::attempt($credentials)) {
              return response()->json(['error' => 'invalid_credentials'], 400);
          }
        } catch (JWTException $e) {
          return response()->json(['error' => 'could_not_create_token'], 500);
        }

        //return response()->json(compact('token'));
        $status = "success";
        $message = "Successfully login user";
        $request_type = "Login";
        $data = array('token'=>$token);

        return $this->response($status,$data,$message,$request_type);
    }

    /**
     * the output format for the api
     *
     * @return Response
     */
    private function response($status,$data,$message,$request_type)
    {
        $response = array(
          'status' => $status,
          'data'  =>  $data,
          'message' => $message,
          'meta'  =>  array(
            'api_version' =>  1,
            'request_type'  =>  $request_type
          )
        );

        return $response;
    }

    /*
    *   The register method validates a user input and creates a user
    *   if the user credentials are validated. The user is then passed on to
    *   JWTAuth to generate an access token for the created user. This way,
    *   the user would not need to log in to get it.
    */
    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:ah_users', //'required|max:255|exists:feeds_user_accounts,username,type_id,0',
            'email' => 'required|string|email|max:255|unique:ah_users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if($validator->fails()){
            //return response()->json($validator->errors()->toJson(), 400);
            return $validator->errors();
        }

        $user = User::create([
            'username' => $request->get('username'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(compact('user','token'),201);
    }

    /*
    *   the getAuthenticatedUser method which returns the user object
    *   based on the authorization token that is passed.
    */
    public function getAuthenticatedUser()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
              return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }

        return response()->json(compact('user'));
    }
}
