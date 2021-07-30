<?php

public function voice(Request $request)
{
    $request->validate([
        'question_id' => 'required|int|exists:questions,id',
        'value' => 'required|boolean',
    ]);

    $question_id = $request->post('question_id');

    // check question existence
    try {
        $question = Question::findOrFail($question_id);
    } catch (ModelNotFoundException $e) {
        return $this->response(
            'Question is not found',
            404
        );
    }

    // question from same user, reject vote
    if ($question->user_id == auth()->id()) {
        return $this->response(
            'You are not allowed to vote to your own question',
            500
        );
    }

    $voice_value = $request->post('value');

    // check if user voted 
    $voice = Voice::where([
        ['user_id', '=', auth()->id()],
        ['question_id', '=', $question_id]
    ])->first();

    // when never voted yet, lets record user's vote
    if (is_null($voice)) {
        return $this->create($question, $voice_value);
    }

    // when found with equal vote, reject it
    if ($voice->value === $voice_value) {
        return $this->response(
            'You are not allowed to vote more than once.',
            500
        );
    }

    // update user's vote
    return $this->update($question, $voice_value);
}

public function create(Question $question, $value)
{
    $question->voice()->create([
        'user_id' => auth()->id(),
        'value' => $value
    ]);

    return $this->response('Voting completed successfully');
}

public function update(Voice $voice, $value)
{
    $voice->update([
        'value' => $value
    ]);

    return $this->response('Your voice is updated.');
}

private function response($message, $status = 200)
{
    return response()->json([
        'status' => $status,
        'message' => $message
    ]);
}
