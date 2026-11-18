<?php

use App\Enums\CefrLevel;
use App\Enums\ExerciseType;
use App\Filament\Resources\ExamQuestions\ExamQuestionResource;
use App\Filament\Resources\ExamQuestions\Pages\EditExamQuestion;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\ExamSections\ExamSectionResource;
use App\Filament\Resources\Exercises\ExerciseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Levels\LevelResource;
use App\Filament\Resources\Passages\Pages\EditPassage;
use App\Filament\Resources\Passages\PassageResource;
use App\Filament\Resources\Passages\RelationManagers\ExamQuestionsRelationManager;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\VocabularyItems\VocabularyItemResource;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSection;
use App\Models\Exercise;
use App\Models\Lesson;
use App\Models\Level;
use App\Models\Passage;
use App\Models\Unit;
use App\Models\User;
use App\Models\VocabularyItem;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Filament::setCurrentPanel('admin');
});

function questionPage(ExamQuestion $question)
{
    return Livewire::test(EditExamQuestion::class, ['record' => $question->getRouteKey()]);
}

/** @return array<string, mixed> */
function questionPayload(ExamQuestion $question, string $side): array
{
    return json_decode($question->fresh()->getRawOriginal($side), true);
}

test('a question loads its type, points and both payloads as JSON', function () {
    $question = ExamQuestion::factory()->create(['points' => 2]);

    questionPage($question)
        ->assertFormSet(['type' => 'multiple_choice', 'points' => 2])
        ->assertFormFieldExists('content')
        ->assertFormFieldExists('solution');
});

test('the points and per-locale explanation of a question are edited', function () {
    $question = ExamQuestion::factory()->create();

    questionPage($question)
        ->set('data.points', 3)
        ->set('data.explanation.en', 'Third person -s.')
        ->set('data.explanation.es', 'La -s de tercera persona.')
        ->call('save')
        ->assertHasNoFormErrors();

    $question->refresh();

    expect($question->points)->toBe(3)
        ->and($question->getTranslation('explanation', 'en'))->toBe('Third person -s.')
        ->and($question->getTranslation('explanation', 'es'))->toBe('La -s de tercera persona.');
});

test('a valid question solution is saved', function () {
    $question = ExamQuestion::factory()->create();
    $current = questionPayload($question, 'solution')['correct'][0];
    $other = collect(questionPayload($question, 'content')['options'])->pluck('id')->first(fn (string $id): bool => $id !== $current);

    questionPage($question)
        ->set('data.solution', json_encode(['correct' => [$other]]))
        ->call('save')
        ->assertHasNoFormErrors();

    expect(questionPayload($question, 'solution')['correct'])->toBe([$other]);
});

test('a question solution whose correct id is not an option is rejected with its path', function () {
    $question = ExamQuestion::factory()->create();
    $before = questionPayload($question, 'solution');

    $errors = questionPage($question)
        ->set('data.solution', json_encode(['correct' => ['zzz']]))
        ->call('save')
        ->assertHasFormErrors(['solution'])
        ->errors();

    expect((string) $errors->first('data.solution'))->toContain('solution.correct.0')
        ->and(questionPayload($question, 'solution'))->toBe($before);
});

test('malformed question JSON is rejected on its field', function () {
    $question = ExamQuestion::factory()->create();
    $before = questionPayload($question, 'content');

    questionPage($question)
        ->set('data.content', '{"options": [')
        ->call('save')
        ->assertHasFormErrors(['content']);

    expect(questionPayload($question, 'content'))->toBe($before);
});

test('a question type change without matching payloads is rejected', function () {
    $question = ExamQuestion::factory()->create();

    questionPage($question)
        ->set('data.type', ExerciseType::FillGap->value)
        ->call('save')
        ->assertHasFormErrors();

    expect($question->fresh()->type)->toBe(ExerciseType::MultipleChoice);
});

test('a question moves to another section and attaches a passage', function () {
    $question = ExamQuestion::factory()->create();
    $target = ExamSection::factory()->create();
    $passage = Passage::factory()->create();

    questionPage($question)
        ->set('data.exam_section_id', $target->id)
        ->set('data.passage_id', $passage->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($question->fresh()->exam_section_id)->toBe($target->id)
        ->and($question->fresh()->passage_id)->toBe($passage->id);
});

test('a question key is not editable', function () {
    $question = ExamQuestion::factory()->create(['key' => 'a1.exam.q01']);

    questionPage($question)
        ->set('data.key', 'hacked')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($question->fresh()->key)->toBe('a1.exam.q01');
});

test('a question is archived and restored and shows the notice', function () {
    $question = ExamQuestion::factory()->create();

    questionPage($question)->assertSee(__('admin.reimport_notice.heading'))->callAction('archive');
    expect($question->fresh()->isArchived())->toBeTrue();

    questionPage($question->fresh())->callAction('restore');
    expect($question->fresh()->isArchived())->toBeFalse();
});

test('Copy as JSON on a question has the section and passage keys and no ids', function () {
    $passage = Passage::factory()->create();
    $question = ExamQuestion::factory()->create(['passage_id' => $passage->id]);

    questionPage($question)
        ->mountAction('copyAsJson')
        ->assertMountedActionModalSee([$question->key, $question->section->key, $passage->key, 'solution'])
        ->assertMountedActionModalDontSee(['exam_section_id', 'passage_id', 'created_at']);
});

test('a question page offers no delete and its resource no creation', function () {
    $question = ExamQuestion::factory()->create();

    questionPage($question)->assertActionDoesNotExist('delete');

    expect(ExamQuestionResource::canCreate())->toBeFalse();
});

test('the exam questions that use a passage are listed read-only', function () {
    $passage = Passage::factory()->create();
    $used = ExamQuestion::factory()->create(['passage_id' => $passage->id]);
    $unrelated = ExamQuestion::factory()->create();

    Livewire::test(ExamQuestionsRelationManager::class, ['ownerRecord' => $passage, 'pageClass' => EditPassage::class])
        ->assertCanSeeTableRecords([$used])
        ->assertCanNotSeeTableRecords([$unrelated])
        ->assertTableActionExists('edit')
        ->assertTableActionDoesNotExist('archive')
        ->assertTableActionDoesNotExist('delete');
});

test('a learner is denied every content resource page', function () {
    $level = Level::factory()->code(CefrLevel::A1)->create();
    $unit = Unit::factory()->for($level)->create();
    $lesson = Lesson::factory()->for($unit)->create();
    $item = VocabularyItem::factory()->for($lesson)->create();
    $exercise = Exercise::factory()->for($lesson)->create();
    $passage = Passage::factory()->create();
    $exam = Exam::factory()->forLevel($level)->create();
    $section = ExamSection::factory()->for($exam)->create();
    $question = ExamQuestion::factory()->for($section, 'section')->create();

    $this->actingAs(User::factory()->withTwoFactor()->create());

    $urls = [
        LevelResource::getUrl('index'),
        LevelResource::getUrl('edit', ['record' => $level]),
        UnitResource::getUrl('edit', ['record' => $unit]),
        LessonResource::getUrl('edit', ['record' => $lesson]),
        VocabularyItemResource::getUrl('edit', ['record' => $item]),
        ExerciseResource::getUrl('edit', ['record' => $exercise]),
        PassageResource::getUrl('index'),
        PassageResource::getUrl('edit', ['record' => $passage]),
        ExamResource::getUrl('index'),
        ExamResource::getUrl('edit', ['record' => $exam]),
        ExamSectionResource::getUrl('edit', ['record' => $section]),
        ExamQuestionResource::getUrl('edit', ['record' => $question]),
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertForbidden();
    }
});

test('an admin opens every exam resource page', function () {
    $exam = Exam::factory()->create();
    $section = ExamSection::factory()->for($exam)->create();
    $question = ExamQuestion::factory()->for($section, 'section')->create();

    $this->get(ExamResource::getUrl('index'))->assertOk();
    $this->get(ExamResource::getUrl('edit', ['record' => $exam]))->assertOk();
    $this->get(ExamSectionResource::getUrl('edit', ['record' => $section]))->assertOk();
    $this->get(ExamQuestionResource::getUrl('edit', ['record' => $question]))->assertOk();
});
