<?php

use App\Enums\CefrLevel;
use App\Enums\ExamKind;
use App\Enums\ExamSkill;
use App\Enums\GradingMode;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Exams\ExamResource;
use App\Filament\Resources\Exams\Pages\EditExam;
use App\Filament\Resources\Exams\Pages\ListExams;
use App\Filament\Resources\Exams\RelationManagers\SectionsRelationManager;
use App\Filament\Resources\ExamSections\ExamSectionResource;
use App\Filament\Resources\ExamSections\Pages\EditExamSection;
use App\Filament\Resources\ExamSections\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\Levels\LevelResource;
use App\Filament\Resources\Passages\PassageResource;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSection;
use App\Models\Level;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->withTwoFactor()->create());
    Filament::setCurrentPanel('admin');
});

function levelExam(CefrLevel $code = CefrLevel::A1, array $attributes = []): Exam
{
    return Exam::factory()->forLevel(Level::factory()->code($code)->create())->create($attributes);
}

function examPage(Exam $exam)
{
    return Livewire::test(EditExam::class, ['record' => $exam->getRouteKey()]);
}

function sectionsOf(Exam $exam): array
{
    return ['ownerRecord' => $exam, 'pageClass' => EditExam::class];
}

test('the navigation lists levels, passages and exams and nothing deeper', function () {
    expect(LevelResource::shouldRegisterNavigation())->toBeTrue()
        ->and(PassageResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ExamResource::shouldRegisterNavigation())->toBeTrue()
        ->and(ExamSectionResource::shouldRegisterNavigation())->toBeFalse()
        ->and(Dashboard::shouldRegisterNavigation())->toBeFalse();
});

test('the exams list searches and hides archived exams until the filter is cleared', function () {
    $active = levelExam(CefrLevel::A1, ['key' => 'a1.exam', 'title' => 'A1 final']);
    $archived = levelExam(CefrLevel::A2, ['key' => 'a2.exam']);
    $archived->archive();

    Livewire::test(ListExams::class)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$archived])
        ->removeTableFilter('archived')
        ->assertCanSeeTableRecords([$active, $archived])
        ->searchTable('final')
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$archived]);
});

test('an exam\'s limits are edited and its key is not', function () {
    $exam = levelExam(CefrLevel::A1, ['key' => 'a1.exam']);

    examPage($exam)
        ->assertFormSet(['key' => 'a1.exam', 'kind' => 'level', 'level_id' => $exam->level_id])
        ->set('data.title', 'A1 final exam')
        ->set('data.time_limit_minutes', 45)
        ->set('data.pass_score', 65)
        ->set('data.max_attempts', 3)
        ->set('data.cooldown_hours', 12)
        ->set('data.key', 'hacked')
        ->call('save')
        ->assertHasNoFormErrors();

    $exam->refresh();

    expect($exam->title)->toBe('A1 final exam')
        ->and($exam->time_limit_minutes)->toBe(45)
        ->and($exam->pass_score)->toBe(65)
        ->and($exam->max_attempts)->toBe(3)
        ->and($exam->cooldown_hours)->toBe(12)
        ->and($exam->key)->toBe('a1.exam');
});

test('unlimited attempts are saved as null', function () {
    $exam = levelExam(CefrLevel::A1, ['max_attempts' => 3]);

    examPage($exam)
        ->set('data.max_attempts', null)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($exam->fresh()->max_attempts)->toBeNull();
});

test('a pass score outside 0-100 is a form error', function () {
    $exam = levelExam();

    examPage($exam)
        ->set('data.pass_score', 101)
        ->call('save')
        ->assertHasFormErrors(['pass_score']);
});

test('a second exam for a level is a form error, not a database error', function () {
    $a1 = levelExam(CefrLevel::A1);
    $a2 = levelExam(CefrLevel::A2);

    examPage($a2)
        ->set('data.level_id', $a1->level_id)
        ->call('save')
        ->assertHasFormErrors(['level_id']);

    expect($a2->fresh()->level_id)->not->toBe($a1->level_id);
});

test('a level exam keeps its own level when saved without moving', function () {
    examPage(levelExam())->call('save')->assertHasNoFormErrors();
});

test('a second active placement exam is a form error, not a database error', function () {
    Exam::factory()->placement()->create();
    $exam = levelExam();

    examPage($exam)
        ->set('data.kind', ExamKind::Placement->value)
        ->call('save')
        ->assertHasFormErrors(['kind']);

    expect($exam->fresh()->kind)->toBe(ExamKind::Level);
});

test('an archived placement exam does not hold the slot', function () {
    $old = Exam::factory()->placement()->create();
    $old->archive();
    $exam = levelExam();

    examPage($exam)
        ->set('data.kind', ExamKind::Placement->value)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($exam->fresh()->kind)->toBe(ExamKind::Placement)
        ->and($exam->fresh()->level_id)->toBeNull();
});

test('a placement exam saves without a level and can edit its limits', function () {
    $placement = Exam::factory()->placement()->create();

    examPage($placement)
        ->set('data.pass_score', 60)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($placement->fresh()->pass_score)->toBe(60)
        ->and($placement->fresh()->level_id)->toBeNull();
});

test('turning a placement exam into a level exam needs a level', function () {
    $placement = Exam::factory()->placement()->create();
    $level = Level::factory()->code(CefrLevel::B1)->create();

    examPage($placement)
        ->set('data.kind', ExamKind::Level->value)
        ->set('data.level_id', null)
        ->call('save')
        ->assertHasFormErrors(['level_id']);

    examPage($placement)
        ->set('data.kind', ExamKind::Level->value)
        ->set('data.level_id', $level->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($placement->fresh()->kind)->toBe(ExamKind::Level)
        ->and($placement->fresh()->level_id)->toBe($level->id);
});

test('an exam is archived and restored from its page and shows the notice', function () {
    $exam = levelExam();

    examPage($exam)->assertSee(__('admin.reimport_notice.heading'))->callAction('archive');
    expect($exam->fresh()->isArchived())->toBeTrue();

    examPage($exam->fresh())->callAction('restore');
    expect($exam->fresh()->isArchived())->toBeFalse();
});

test('Copy as JSON on an exam leaves out its level and kind', function () {
    $exam = levelExam(CefrLevel::A1);

    examPage($exam)
        ->mountAction('copyAsJson')
        ->assertMountedActionModalSee([$exam->key, $exam->title])
        ->assertMountedActionModalDontSee(['"level"', '"kind"', 'level_id', 'created_at']);
});

test('the sections relation lists an exam\'s sections ordered by position and shows their kind', function () {
    $exam = levelExam();
    $second = ExamSection::factory()->for($exam)->create(['position' => 2]);
    $first = ExamSection::factory()->for($exam)->ai(ExamSkill::Writing)->create(['position' => 1]);
    $other = ExamSection::factory()->for(levelExam(CefrLevel::B2))->create(['position' => 1]);

    Livewire::test(SectionsRelationManager::class, sectionsOf($exam))
        ->assertCanSeeTableRecords([$first, $second], inOrder: true)
        ->assertCanNotSeeTableRecords([$other])
        ->assertSee(['use_of_english', 'writing', 'ai']);
});

test('sections are reordered, archived and restored in the relation', function () {
    $exam = levelExam();
    $one = ExamSection::factory()->for($exam)->create(['position' => 1]);
    $two = ExamSection::factory()->for($exam)->create(['position' => 2]);

    Livewire::test(SectionsRelationManager::class, sectionsOf($exam))
        ->call('reorderTable', [$two->id, $one->id])
        ->callAction(TestAction::make('archive')->table($one))
        ->removeTableFilter('archived')
        ->callAction(TestAction::make('restore')->table($one));

    expect($two->fresh()->position)->toBe(1)
        ->and($one->fresh()->position)->toBe(2)
        ->and($one->fresh()->isArchived())->toBeFalse();
});

test('the sections relation offers no create or delete', function () {
    $exam = levelExam();
    ExamSection::factory()->for($exam)->create();

    Livewire::test(SectionsRelationManager::class, sectionsOf($exam))
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('delete')
        ->assertTableBulkActionDoesNotExist('delete');

    expect(ExamResource::canCreate())->toBeFalse()
        ->and(ExamSectionResource::canCreate())->toBeFalse();
});

test('a section\'s skill, grading mode, points and subset size are edited', function () {
    $section = ExamSection::factory()->create(['key' => 'a1.exam.uoe']);

    Livewire::test(EditExamSection::class, ['record' => $section->getRouteKey()])
        ->set('data.skill', ExamSkill::Reading->value)
        ->set('data.grading_mode', GradingMode::SelfAssessment->value)
        ->set('data.points', 30)
        ->set('data.questions_per_attempt', 10)
        ->set('data.key', 'hacked')
        ->call('save')
        ->assertHasNoFormErrors();

    $section->refresh();

    expect($section->skill)->toBe(ExamSkill::Reading)
        ->and($section->grading_mode)->toBe(GradingMode::SelfAssessment)
        ->and($section->points)->toBe(30)
        ->and($section->questions_per_attempt)->toBe(10)
        ->and($section->key)->toBe('a1.exam.uoe');
});

test('an empty subset size means every question', function () {
    $section = ExamSection::factory()->create(['questions_per_attempt' => 5]);

    Livewire::test(EditExamSection::class, ['record' => $section->getRouteKey()])
        ->set('data.questions_per_attempt', null)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($section->fresh()->questions_per_attempt)->toBeNull();
});

test('a section moved onto a taken position is a form error, not a database error', function () {
    $target = levelExam(CefrLevel::B1);
    ExamSection::factory()->for($target)->create(['position' => 1]);
    $section = ExamSection::factory()->for(levelExam(CefrLevel::A1))->create(['position' => 1]);

    Livewire::test(EditExamSection::class, ['record' => $section->getRouteKey()])
        ->set('data.exam_id', $target->id)
        ->call('save')
        ->assertHasFormErrors(['position']);

    expect($section->fresh()->exam_id)->not->toBe($target->id);
});

test('a section moves to another exam at a free position', function () {
    $target = levelExam(CefrLevel::B1);
    $section = ExamSection::factory()->for(levelExam(CefrLevel::A1))->create(['position' => 1]);

    Livewire::test(EditExamSection::class, ['record' => $section->getRouteKey()])
        ->set('data.exam_id', $target->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($section->fresh()->exam_id)->toBe($target->id);
});

test('an exam, then one of its sections, leads to the section\'s questions', function () {
    $exam = levelExam();
    $section = ExamSection::factory()->for($exam)->create();
    $question = ExamQuestion::factory()->for($section, 'section')->create();
    $archived = ExamQuestion::factory()->for($section, 'section')->create();
    $archived->archive();

    $this->get(ExamResource::getUrl('edit', ['record' => $exam]))->assertOk();
    $this->get(ExamSectionResource::getUrl('edit', ['record' => $section]))->assertOk();

    Livewire::test(QuestionsRelationManager::class, ['ownerRecord' => $section, 'pageClass' => EditExamSection::class])
        ->assertCanSeeTableRecords([$question])
        ->assertCanNotSeeTableRecords([$archived])
        ->removeTableFilter('archived')
        ->assertCanSeeTableRecords([$question, $archived]);
});

test('a section is archived and restored from its page', function () {
    $section = ExamSection::factory()->create();

    Livewire::test(EditExamSection::class, ['record' => $section->getRouteKey()])->callAction('archive');
    expect($section->fresh()->isArchived())->toBeTrue();

    Livewire::test(EditExamSection::class, ['record' => $section->getRouteKey()])->callAction('restore');
    expect($section->fresh()->isArchived())->toBeFalse();
});
