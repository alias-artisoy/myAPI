<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Meeting;
use Illuminate\Http\Request;
use JWTAuth;

class MeetingController extends Controller
{
    public function __construct(){
        $this->middleware('jwt.auth',['only' => [
            'update','store','destroy'
        ]]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetings = Meeting::all();
        foreach($meetings as $meeting){
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/' . $meeting->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'List of all meetings.',
            'meetings' => $meetings
        ];
        
        return response()->json($response,200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie'
        ]);

        if(!$user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg' => 'User not found.'],404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meeting = new Meeting([
            'time' => Carbon::createFromFormat('YmdHie', $time),
            'title' => $title,
            'description' => $description
        ]);

        if($meeting->save()){
            $meeting->users()->attach($user_id);
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/1',
                'method' => 'GET'
            ];
            $response = [
            'msg' => 'Meeting created successfully',
            'meeting' => $meeting
            ];
            return response()->json($response, 201);
        }
        
        $response = [
            'msg' => 'An error occurred.'
        ];
        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();
        $meeting->view_meetings = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];
        $response = [
            'msg' => 'Meeting information',
            'meeting' => $meeting
        ];
        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {   
        $this->validate($request,[
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie'
        ]);

        if(!$user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg' => 'User not found.'],404);
        }
        
        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meeting = [
            'title' => $title,
            'description' => $description,
            'time' => $time,
            'user_id' => $user_id,
            'view_meeting' => [
                'href' => 'api/v1/meeting/1',
                'method' => 'GET'
            ]
        ];
        $meeting = Meeting::with('users')->findOrFail($id);

        if(!$meeting->users()->where('users.id',$user_id)->first()){
            return response()->json(['msg' =>  'User not registered for meeting, update not successful', 401]);
        };

        $meeting->time = Carbon::createFromFormat('YmdHie', $time);
        $meeting->title = $title;
        $meeting->description = $description;
        
        if(!$meeting->update()){
            return response()->json(['msg' => 'Error during updating.', 404]);
        }

        $meeting->view_meeting = [
              'href' => 'api/v1/meeting/' . $meeting->id,
              'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting updated successfully',
            'meeting' => $meeting
        ];
        return response()->json($response,200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        
        if(!$user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg' => 'User not found.'],404);
        }

        $users = $meeting->users;
        $meeting->users()->detach();

        if(!$meeting->delete()){
             return response()->json(['msg' => 'deletion failed'],200);
        }
        
        if(!$meeting->users()->where('users.id',$user->id)->first()){
            return response()->json(['msg' =>  'User not registered for meeting, update not successful', 401]);
        };
        $response = [
            'msg' => 'Meeting deleted successfully',
            'create' => [
                'href' => 'api/v1/meeting/1',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];
        
        return response()->json($response,200);
    }
}
