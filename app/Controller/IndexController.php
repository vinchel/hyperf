<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use Hyperf\DbConnection\Db;

class IndexController extends AbstractController
{
    public function index()
    {
        $data = 'aaa';

        try {
            Db::beginTransaction(); // 开始事务

            // 第一步：插入数据 "ddd"
            Db::table('user')->insert(['name' => 'ddd']);

            $channel = new \Swoole\Coroutine\Channel();
            go(function () use ($channel) {
                try {
                    // 第二步: 执行 ALTER TABLE 语句
                    Db::statement('ALTER TABLE `test` ADD COLUMN `name` varchar(255) NULL AFTER `id`');
                    $channel->push(1);
                } catch (\Throwable $ex) {
                    // 如果 ALTER TABLE 失败，设置标志为失败
                    $channel->push(0);
                }
            });

            $success = $channel->pop();
            if ($success != 1) {
                throw new \Exception('go error');
            }

            // 第三步：插入user数据 "eee"
            Db::table('user')->insert(['name' => 'eee']);

            // 第三步：基于更改后再插入test数据 "fff"
            Db::table('test')->insert(['name' => 'ffff']);

            Db::commit();

        } catch (\Throwable $ex) {
            echo 'throw exception: '. $ex->getMessage().PHP_EOL;
            // 捕获其他异常并进行处理
            Db::rollBack();

            //进行ddl回滚操作
            Db::statement('ALTER TABLE `test` DROP COLUMN `name`');
        }

        return ['data' => $data];
    }

    public function test()
    {
        $user = $this->request->input('user', 'Hyperf');
        $method = $this->request->getMethod();

        return [
            'method' => $method,
            'message' => "Hello {$user}.",
        ];
    }
}
