<?php

$homeWorkNum = '4.2';
$homeWorkCaption = 'Запросы SELECT, INSERT, UPDATE и DELETE.';

$host = 'localhost';
$db = 'global';
$user = 'garetov';
$password = 'neto1262';

const TASK_STATE_COMPLETE = 2;
const TASK_STATE_IN_PROGRESS = 1;

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sqlCreate = "CREATE TABLE IF NOT EXISTS tasks (
  id int(11) NOT NULL AUTO_INCREMENT,
  description text NOT NULL,
  is_done tinyint(4) NOT NULL DEFAULT '0',
  date_added datetime NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

$statement = $pdo->prepare($sqlCreate);
$statement->execute();

$description = getValueFromRequest('description');

/**
 * Действия при нажатии Добавить.
 */
if (!empty($description) && empty(getValueFromRequest('action'))) {
    $sqlAdd = "INSERT INTO tasks (description, is_done, date_added) VALUES (?, ?,  NOW() )";
    $statement = $pdo->prepare($sqlAdd);
    $statement->execute([$description, TASK_STATE_IN_PROGRESS]);
    $description = '';
}

/**
 * Действия, если была нажата одна из ссылок - Изменить, Выполнить или Удалить
 */
if (!empty(getValueFromRequest('id')) && !empty(getValueFromRequest('action'))) {
    $id = (int)getValueFromRequest('id');
    switch (getValueFromRequest('action')) {
        case 'edit':
            if (!empty($description)) {
                $sqlEdit = "UPDATE tasks SET description = ? WHERE id = ?";
                $statement = $pdo->prepare($sqlEdit);
                $statement->execute([$description, $id]);
                if (!headers_sent()) {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $sqlEdit = "SELECT description FROM tasks WHERE id = ?";
                $statement = $pdo->prepare($sqlEdit);
                $statement->execute([$id]);
                $description = $statement->fetch(PDO::FETCH_ASSOC)['description'];
            }
            break;
        case 'done':
            $sqlDone = "UPDATE tasks SET is_done = ? WHERE id = ?";
            $statement = $pdo->prepare($sqlDone);
            $statement->execute([TASK_STATE_COMPLETE, $id]);
            if (!headers_sent()) {
                header('Location: index.php');
                exit;
            }
            break;
        case 'delete':
            $sqlDel = "DELETE FROM tasks WHERE id = ?";
            $statement = $pdo->prepare($sqlDel);
            $statement->execute([$id]);
            if (!headers_sent()) {
                header('Location: index.php');
                exit;
            }
            break;
    }
}

/**
 * Получаем список задач в зависимости от режима сортировки
 */
$sort = !empty(getValueFromRequest('sort_by')) ? getValueFromRequest('sort_by') : 'date_created';
switch ($sort) {
    case 'date_created':
        $sql = "SELECT * FROM tasks ORDER BY date_added";
        break;
    case 'is_done':
        $sql = "SELECT * FROM tasks ORDER BY is_done";
        break;
    case 'description':
        $sql = "SELECT * FROM tasks ORDER BY description";
        break;
    default:
        $sql = "SELECT * FROM tasks ORDER BY date_added";
}

$statement = $pdo->prepare($sql);
$statement->execute([]);


/**
 * Возвращает содержимое $_GET[$request] или пустую строку
 * @param $request
 * @return string
 */
function getValueFromRequest($request)
{
    if (!empty($_GET[$request])) {
        return htmlspecialchars($_GET[$request]);
    }
    if (!empty($_POST[$request])) {
        return htmlspecialchars($_POST[$request]);
    }
    return '';
}

/**
 * Возвращает название статуса задачи
 * @param $id
 * @return string
 */
function getStatusName($id)
{
    switch ($id) {
        case TASK_STATE_IN_PROGRESS:
            return 'В процессе';
            break;
        case TASK_STATE_COMPLETE:
            return 'Завершено';
            break;
        default:
            return '';
            break;
    }
}

/**
 * Возвращает цвет для выделения статуса задачи
 * @param $id
 * @return string
 */
function getStatusColor($id)
{
    switch ($id) {
        case TASK_STATE_IN_PROGRESS:
            return 'orange';
            break;
        case TASK_STATE_COMPLETE:
            return 'green';
            break;
        default:
            return 'red';
            break;
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
  <head>
    <title>Домашнее задание по теме <?= $homeWorkNum ?> <?= $homeWorkCaption ?></title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="styles.css">
  </head>
  <body>
    <h1>Список дел на сегодня</h1>
    <div style="float: left">
      <form method="POST">
        <input type="text" name="description" placeholder="Описание задачи" value="<?= $description ?>"/>
        <input type="submit" name="save"
               value="<?= isset($_GET['action']) && $_GET['action'] === 'edit' ? 'Сохранить' : 'Добавить' ?>"/>
      </form>
    </div>
    <div style="float: left; margin-left: 20px;">
      <form method="POST">
        <label for="sort">Сортировать по:</label>
        <select name="sort_by">
          <option <?= $sort === 'date_created' ? 'selected' : '' ?> value="date_created">Дате добавления</option>
          <option <?= $sort === 'is_done' ? 'selected' : '' ?> value="is_done">Статусу</option>
          <option <?= $sort === 'description' ? 'selected' : '' ?> value="description">Описанию</option>
        </select>
        <input type="submit" name="sort" value="Отсортировать"/>
      </form>
    </div>
    <div style="clear: both"></div>

    <table>
      <tr>
        <th>Описание задачи</th>
        <th>Дата добавления</th>
        <th>Статус</th>
        <th>Управление задачей</th>
      </tr>

      <?php while ($row = $statement->fetch(PDO::FETCH_ASSOC)) : ?>
      <tr>
        <td><?= $row['description'] ?></td>
        <td><?= $row['date_added'] ?></td>
        <td><span style='color: <?= getStatusColor($row['is_done']) ?>;'><?= getStatusName($row['is_done']) ?></span></td>
        <td>
          <a href='?id=<?= $row['id'] ?>&action=edit'>Изменить</a>
          <a href='?id=<?= $row['id'] ?>&action=done'>Выполнить</a>
          <a href='?id=<?= $row['id'] ?>&action=delete'>Удалить</a>
        </td>
      </tr>
      <?php endwhile; ?>

      <table>
  </body>
</html>
