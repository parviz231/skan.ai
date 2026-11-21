<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once("config2.php");

$qid      = isset($_GET['id']) ? intval($_GET['id']) : 0;
$topic_id = isset($_GET['topic_id']) ? intval($_GET['topic_id']) : 0;
if ($qid === 0) die("Вопрос не выбран");

$stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->bind_param("i", $qid);
$stmt->execute();
$q = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$q) die("Вопрос не найден");

require_once("practice_header.php");
?>

<div class="container py-5" style="max-width: 720px;">
    <a href="module.php?id=<?= $topic_id ?>" class="btn btn-sm btn-outline-secondary mb-3">
        ← Назад к вопросам
    </a>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="card-title mb-4"><?= htmlspecialchars($q['question_text']) ?></h4>

            <form action="check_test.php" method="POST">
                <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                <input type="hidden" name="topic_id" value="<?= $topic_id ?>">

                <?php
                $options = ['A'=>$q['option_a'], 'B'=>$q['option_b'],
                            'C'=>$q['option_c'], 'D'=>$q['option_d']];
                foreach ($options as $letter => $text):
                    if (trim((string)$text) === '') continue;
                ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio"
                               name="answer" value="<?= $letter ?>"
                               id="opt_<?= $letter ?>">
                        <label class="form-check-label" for="opt_<?= $letter ?>">
                            <strong><?= $letter ?>.</strong> <?= htmlspecialchars($text) ?>
                        </label>
                    </div>
                <?php endforeach; ?>

                <button class="btn btn-primary mt-3">Ответить</button>
            </form>
        </div>
    </div>
</div>  