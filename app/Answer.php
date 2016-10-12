<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    public function add()
    {
        if (!get_user_instance()->is_logged_in()) {
            return ['status' => 0, 'msg' => '请先登录'];
        }
        if (!rq('question_id') || !rq('content')) {
            return ['status' => 0, 'msg' => 'question_id content 都不能为空'];
        }
        $question = get_question_instance()->find(rq('question_id'));
        if (!$question) {
            return ['status' => 0, 'msg' => '问题不存在'];
        }
//同一个问题，一个人只能回答一次
        $answered = $this
            ->where(['question_id' => rq('question_id'), 'user_id' => session('user_id')])
            ->count();
        if ($answered) {
            return ['status' => 0, 'msg' => '你已经回答过此问题，禁止重复回答'];
        }
        $this->content = rq('content');
        $this->question_id = rq('question_id');
        $this->user_id = session('user_id');
        if ($this->save()) {
            return ['status' => 1, 'id' => $this->id];
        }
        return ['status' => 0, 'msg' => '数据库插入失败'];
    }

    public function change()
    {
        if (!get_user_instance()->is_logged_in()) {
            return ['status' => 0, 'msg' => '请先登录'];
        }
        $id = rq('id');
        $content = rq('content');
        if (!$id || !$content) {
            return ['status' => 0, 'msg' => 'id 和 content 都不能为空'];
        }
        $answer = $this->find($id);//返回主键所在行
        if (!$answer) {
            return ['status' => 0, 'msg' => '回答不存在'];
        }
        if ($answer->user_id != session('user_id')) {
            return ['status' => 0, 'msg' => '您没有权限修改'];
        }
        $answer->content = $content;
        if ($answer->save()) {
            return ['status' => 1, 'msg' => '修改成功'];
        }
        return ['status' => 0, 'msg' => '数据库插入失败'];
    }

    public function read()
    {
        $id = rq('id');
        $question_id = rq('question_id');
        if (!$id && !$question_id) {
            return ['status' => 0, 'msg' => 'id或者question_id不能为空'];
        }
        if ($id) {
            //查看某个回答
            $answer = $this->find($id);
            if (!$answer) {
                return ['status' => 0, 'msg' => '回答不存在'];
            }
            return ['status' => 1, 'data' => $answer];
        }

        //查找问题前，查看问题是否存在
        if (!get_question_instance()->find($question_id)) {
            return ['status' => 0, 'msg' => '问题不存在'];
        }

        //查看所有回答
        $answer = $this
            ->where('question_id', $question_id)
            ->get()
            ->keyBy('id');
        return ['status' => 1, 'data' => $answer];
    }

    //投票
    public function vote()
    {
        if (!get_user_instance()->is_logged_in()) {
            return ['status' => 0, 'msg' => '请先登录'];
        }
        $id = rq('id');
        if (!$id || !rq('vote')) {
            return ['status' => 0, 'msg' => 'id vote 都不能为空'];
        }
        $answer = $this->find($id);
        if (!$answer) {
            return ['status' => 0, 'msg' => '问题不存在'];
        }
        $vote = rq('vote') <= 1 ? 1 : 2; //1-赞同 2-反对

        //检查此用户是否在相同的问题下投过票
        $vote_ins = $answer
            ->users()
            ->newPivotStatement()
            ->where('user_id', session('user_id'))
            ->where('answer_id', $id)
            ->delete();

        $answer->users()->attach(session('user_id'), ['vote' => $vote]);
        return ['status' => 1];

    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function users()
    {
        return $this
            ->belongsToMany('App\User')
            ->withPivot('vote')
            ->withTimestamps();
    }
}
