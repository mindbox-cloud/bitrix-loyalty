<?php

namespace Mindbox\Loyalty\ORM;

use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Mindbox\Exceptions\MindboxClientErrorException;
use Mindbox\Exceptions\MindboxClientException;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Loyalty\Api;
use Mindbox\MindboxRequest;

class QueueTable extends Entity\DataManager
{
    private const COUNT_CALL = 5;

    public static function push(MindboxRequest $request, string $siteId)
    {
        $result = self::add(['REQUEST_DATA' => serialize($request), 'SITE_ID' => $siteId]);

        return $result;
    }

    public static function execute()
    {
        $dbTasks = self::getList([
            'filter' => ['=STATUS_EXEC' => 'N', '!SITE_ID' => false, '<=COUNT_CALL' => self::COUNT_CALL],
            'select' => ['*'],
            'order' => ['ID' => 'ASC']
        ]);

        while ($task = $dbTasks->fetch()) {
            $request = unserialize($task['REQUEST_DATA']);
            if (!$request instanceof MindboxRequest) {
                continue;
            }

            try {
                // todo необходимо получать клиента по SITE_ID
                $client = Api::getInstance($task['SITE_ID'])->getClient();

                $client->setRequest($request)->sendRequest();
                $status = 'Y';
            } catch (MindboxClientErrorException $e) {
                // Помечаем 400 ответы
                $status = 'F';
            } catch (MindboxUnavailableException $e) {
                $status = 'N';
            } catch (MindboxClientException $e) {
                $status = 'N';
            }

            self::update(
                $task['ID'],
                [
                    'STATUS_EXEC' => $status,
                    'DATE_EXEC' =>  new \Bitrix\Main\Type\DateTime(),
                    'COUNT_CALL' => $task['COUNT_CALL'] ? $task['COUNT_CALL'] + 1 : 1,
                ]
            );
        }
    }

    public static function getTableName()
    {
        return 'lbi_queue';
    }

    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true
            ]),
            new Entity\TextField('REQUEST_DATA'),
            new Entity\StringField('SITE_ID'),
            new Entity\DatetimeField('DATE_INSERT', [
                'default_value' => new \Bitrix\Main\Type\DateTime()
            ]),
            new Entity\DatetimeField('DATE_EXEC',[
                'title' => 'DATE_EXEC',
                'nullable' => true
            ]),
            new Entity\IntegerField('COUNT_CALL', [
                    'default_value' => 0
            ]),
            new Entity\EnumField('STATUS_EXEC', [
                'values' => ['N', 'Y', 'F'],
                'default_value' => 'N'
            ])
        ];
    }
}
