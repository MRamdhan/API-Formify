<?php

namespace App\Http\Controllers;

use App\Models\AllowedDomain;
use App\Models\Form;
use App\Models\Question;
use App\Models\Response;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FormController extends Controller
{
    function createForm(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'slug' => 'required|regex:/^[a-zA-Z-0-9-.]+$/|unique:forms,slug',
            'allowed_domains' => 'required|array',
            'description' => 'required',
            'limit_one_response' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Invalid field',
                'errors' => $validator->errors()
            ], 422);
        }

        $form = new Form();
        $form->name = $request->name;
        $form->slug = $request->slug;
        $form->description = $request->description;
        $form->limit_one_response = $request->limit_one_response;
        $form->creator_id = auth()->id();
        $form->save();
        
        $user = auth()->user();
        if($user){
            return response()->json([
                'message' => 'Create form success.',
                'form' => $form 
            ], 200);
        }

        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);

    }

    function getforms() {
        $forms = Form::all();
        if($forms){
            return response()->json([
                'message' => 'Get all forms success',
                'forms' => $forms
            ], 200);
        }

        return response()->json([
            'message' => 'Unauthenticade'
        ], 401);
    }

    function formSlug($form_slug)
    {
        $form = Form::where('slug', $form_slug)->with(['allowed_domains','questions'])->first();

        if (!$form) {
            return response()->json([
                'message' => 'Form not found'
            ], 404);
        }

        $user = User::where('id',auth()->id())->first();

        $domains =  substr(strrchr($user->email,'@'), 1);
        $allowedDomain = AllowedDomain::where('form_id',$form->id)->get();
        $udomain = $allowedDomain->pluck('domain')->toArray();

        if(!in_array($domains,$udomain)){
            return response()->json([
                'message' => 'Forbidden access',
            ], 403);
        }

        return response()->json([
            'message' => 'Get form success',
            'form' => $form
        ], 200);
    }

    public function addQuestions(Request $request, $form_slug)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'choice_type' => 'required|in:short answer,paragraph,date,multiple choice,dropdown,checkboxes',
            'is_required' => 'required',
            'choices' => 'required_if:choice_type,multiple choice,dropdown,checkboxes',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid field',
                'errors' => $validator->errors()
            ], 422);
        }

        $form = Form::where('slug',$form_slug)->first();

        if (!$form) {
            return response()->json([
                'message' => 'Form not found',  
            ], 404);
        }

        if ($form->creator_id != auth()->id()) {
            return response()->json([
                'message' => 'Forbidden access'
            ], 403);
        }

        $question = new Question();
        $question->name = $request->name;
        $question->choice_type = $request->choice_type;
        $question->is_required = $request->is_required;
        $question->choices = json_encode($request->choices);
        // $question->choices = implode(',',$request->choices);
        $question->form_id = $form->id;
        $question->save();

        return response()->json([
            'message' => 'Add question success',
            'question' => $question->only([
                'name', 'choice_type', 'is_required', 'choices', 'form_id', 'id'
                ])
        ], 200);
    }

    function removequestions($form_slug, $question_id ) {
        $form = Form::where('slug', $form_slug)->first();
    
        if (!$form) {
            return response()->json([
                'message' => 'Form not found'
            ], 404);
        }
    
        $question = Question::find($question_id);
    
        if (!$question) {
            return response()->json([
                'message' => 'Question not found'
            ], 404);
        }
        $user = User::find(auth()->id());

        if (!$user || $form->creator_id != $user->id) {
            return response()->json([
                'message' => 'Forbidden access'
            ], 403);
        }

        $question->delete();
    
        return response()->json([
            'message' => 'Remove question success'
        ], 200);
    }

    function responses(Request $request, $form_slug) {
        $validator = Validator::make($request->all(), [
            'answers' => [
                'questions_id' => 'required_if:questions,is_required,true',
                'value' => 'required'
                ]
        ]);
        
        if($validator->fails()){
            return response()->json([
                'message' => 'Invalid field',
                'errors' => $validator->errors()
            ], 422);
        }

        $form = Form::where('slug', $form_slug)->first();

        if(!$form){
            return response()->json([
                'message' => 'Form not found'
            ], 404);
        }

        $user = auth()->user();
        $domains =  substr(strrchr($user->email,'@'), 1);
        $allowedDomain = AllowedDomain::where('form_id',$form->id)->get();
        $udomain = $allowedDomain->pluck('domain')->toArray();

        if(!in_array($domains,$udomain)){
            return response()->json([
                'message' => 'Forbidden access',
            ], 403);
        }

        $respon = Response::where('form_id',$form->id)->where('user_id',$user->id)->exists();

        if($form->limit_one_response && $respon){
            return response()->json([
                'message' => 'You can not submit form twice'
            ], 422);
        }

        $response = new Response();
        $response->user_id = $user->id;
        $response->form_id = $form->id;
        $response->date = now();
        $response->save();


        return response()->json([
            'message' => 'Submit response success'
        ], 200);
    }

    function getResponses($form_slug) {
        $form = Form::where('slug', $form_slug)->first();
    
        if (!$form) {
            return response()->json([
                'message' => 'Form not found'
            ], 404);
        }
    
        $responses = Response::where('form_id', $form->id)
            ->with(['user', 'answers'])
            ->get();
    
        $user = auth()->user();
    
        if (!$user || $form->creator_id != $user->id) {
            return response()->json([
                'message' => 'Forbidden access'
            ], 403);
        }

        $transformedResponses = $responses->map(function ($response) {
            $answers = $response->answers->mapWithKeys(function ($answer) {
                $question = Question::find($answer->question_id);
    
                return [$question->name => $answer->value];
            })->toArray();
    
            return [
                'date' => $response->date, 
                'user' => [
                    'id' => $response->user->id,
                    'name' => $response->user->name,
                    'email' => $response->user->email,
                    'email_verified_at' => $response->user->email_verified_at,
                ],
                'answers' => $answers,
            ];
        });
    
        return response()->json([
            'message' => 'Get responses success',
            'responses' => $transformedResponses
        ], 200);
    }
        
    
}
