;
(function () {
    'use strict';
    angular.module('answer', [])

        .service('AnswerService', [
            '$http',
            function ($http) {
                var me = this;
                me.data = {};
                /**
                 * 统计票数
                 * @param answers array
                 * @returns {*}
                 */
                me.count_vote = function (answers) {
                    for (var i = 0; i < answers.length; i++) {
                        var votes, item = answers[i];
                        item.upvote_count = 0;
                        item.downvote_count = 0;
                        if (!item['question_id'] || !item['users']) {
                            continue;
                        }
                        votes = item['users'];
                        if (votes) {
                            for (var j = 0; j < votes.length; j++) {
                                var v = votes[j];
                                if (v['pivot'].vote === 1) {
                                    item.upvote_count++;
                                }
                                if (v['pivot'].vote === 2) {
                                    item.downvote_count++;
                                }
                            }
                        }
                    }
                    return answers;
                }

                me.vote = function (conf) {
                    if (!conf.id || !conf.vote) {
                        console.log(' id 和 vote 不存在');
                    }
                    return $http.post('api/answer/vote', conf)
                        .then(function (r) {
                            if (r.data.status) {
                                return true;
                            }
                            return false;
                        }, function () {
                            return false;
                        })
                }

                me.update_data = function (id) {
                    return $http.post('/api/answer/read', {id: id})
                        .then(function (r) {
                            me.data[id] = r.data.data;
                        }, function () {

                        })
                }
            }
        ])

})();