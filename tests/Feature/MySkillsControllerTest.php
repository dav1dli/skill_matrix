<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\{Skill, SkillGroup, User, UserSkill};
use Tests\TestCase;
use Faker\Factory;

class MySkillControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user = null;
    protected $skillGroup = null;

    public function setUp()
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->skillGroup = factory(SkillGroup::class)->create();
    }

    public function testShowsAllSkillGroups()
    {
        $this->loginRequired('get', 'skills.my.index');

        $user = factory(User::class)->create();

        $response = 
            $this->actingAs($user)
                ->from(route('login'))
                ->get(route('skills.my.index'))
                ->assertStatus(200)
                ->assertSee('Übersicht')
                ->assertSee('Meine Skills');

        $skillGroups = SkillGroup::with('skills')->orderBy('name', 'asc');
        foreach($skillGroups as $skillGroup) {
            $response->assertSee(e($skillGroup->name));
            foreach($skillGroups->skills() as $skill) {
                $response->assertSee(e($skill->name));
            }
        }
    }

    public function testShowsCreateASkill()
    {
        $skill = factory(Skill::class)->create();

        $this->loginRequired('get', 'skills.my.create', ['skill_id' => $skill->id]);

        $this->actingAs($this->user)
            ->from(route('skills.my.index'))
            ->get(route('skills.my.create', ['skill_id' => $skill->id]))
            ->assertStatus(200)
            ->assertSee(e('Skill eintragen'));
    }

    public function testTriesToStoreASkill()
    {
        $skill = factory(Skill::class)->create();
        $allUserSkillsCount = UserSkill::count();

        $this->loginRequired('post', 'skills.my.store', ['skill_id' => $skill->id]);

        $this->actingAs($this->user)
            ->from(route('skills.my.create', ['skill_id' => $skill->id]))
            ->post(route('skills.my.store', ['skill_id' => $skill->id]), [
                'user_skill' => [
                    'grade' => null,
                ]
            ])
            ->assertStatus(302)
            ->assertRedirect(route('skills.my.create', ['skill_id' => $skill->id]))
            ->assertSessionHasErrors([
                'user_skill.grade' => 'Bewerten Sie ihr Können mit einer Schulnote (1-6).',
            ])
        ;

        $this->actingAs($this->user)
            ->from(route('skills.my.create', ['skill_id' => $skill->id]))
            ->post(route('skills.my.store', ['skill_id' => $skill->id]), [
                'user_skill' => [
                    'grade' => 9999,
                ]
            ])
            ->assertStatus(302)
            ->assertRedirect(route('skills.my.create', ['skill_id' => $skill->id]))
            ->assertSessionHasErrors([
                'user_skill.grade' => 'Bewerten Sie ihr Können mit einer Schulnote (1-6).',
            ])
        ;

        $this->actingAs($this->user)
            ->from(route('skills.my.create', ['skill_id' => $skill->id]))
            ->post(route('skills.my.store', ['skill_id' => $skill->id]), [
                'user_skill' => [
                    'grade' => 'a',
                ]
            ])
            ->assertStatus(302)
            ->assertRedirect(route('skills.my.create', ['skill_id' => $skill->id]))
            ->assertSessionHasErrors([
                'user_skill.grade' => 'Bewerten Sie ihr Können mit einer Schulnote (1-6).',
            ])
        ;

        $this->assertEquals($allUserSkillsCount, UserSkill::count());
    }

    public function testTriesToStoreAnExistingSkill()
    {
        $skill = factory(Skill::class)->create();
        $allUserSkillsCount = UserSkill::count();

        $this->user->skills()->attach($skill->id, ['grade' => 1]);
        $skillIds = $this->user->skills()->pluck('skills.id')->all();
        $this->assertTrue(in_array($skill->id, $skillIds));

        $this->loginRequired('post', 'skills.my.store', ['skill_id' => $skill->id]);

        $this->actingAs($this->user)
            ->from(route('skills.my.create', ['skill_id' => $skill->id]))
            ->post(route('skills.my.store', ['skill_id' => $skill->id]), [
                'user_skill' => [
                    'grade' => 2,
                ]
            ])
            ->assertStatus(302)
            ->assertRedirect(route('skills.my.index'))
            ->assertSessionHas('flash_notice', 'Ihr Skill ' . $skill->name . ' wurde eingetragen.')
        ;

        $skillIds = $this->user->skills()->pluck('skills.id')->all();
        $this->assertTrue(in_array($skill->id, $skillIds));

        $this->assertEquals(1, $this->user->skills()->where('skill_id', $skill->id)->count());
        $userSkill = UserSkill::where('user_id', $this->user->id)->where('skill_id', $skill->id)->first();
        $this->assertEquals(2, $userSkill->grade);
    }

    public function testStoresASkill()
    {
        $skill = factory(Skill::class)->create();
        $allUserSkillsCount = UserSkill::count();

        $skillIds = $this->user->skills()->pluck('skills.id')->all();
        $this->assertTrue(!in_array($skill->id, $skillIds));

        $this->loginRequired('post', 'skills.my.store', ['skill_id' => $skill->id]);

        $this->actingAs($this->user)
            ->from(route('skills.my.create', ['skill_id' => $skill->id]))
            ->post(route('skills.my.store', ['skill_id' => $skill->id]), [
                'user_skill' => [
                    'grade' => 1,
                ]
            ])
            ->assertStatus(302)
            ->assertRedirect(route('skills.my.index'))
            ->assertSessionHas('flash_notice', 'Ihr Skill ' . $skill->name . ' wurde eingetragen.')
        ;

        $skillIds = $this->user->skills()->pluck('skills.id')->all();
        $this->assertTrue(in_array($skill->id, $skillIds));
    }

    public function testShowsEditASkill()
    {
        $skill = factory(Skill::class)->create();
        $this->user->skills()->attach($skill->id, ['grade' => 3]);

        $this->loginRequired('get', 'skills.my.edit', ['skill_id' => $skill->id]);

        $this->actingAs($this->user)
            ->from(route('skills.my.index'))
            ->get(route('skills.my.edit', ['skill_id' => $skill->id]))
            ->assertStatus(200)
            ->assertSee(e('Skill ändern'));
    }

    public function testTriesToUpdateAnExistingSkill()
    {
        $skill = factory(Skill::class)->create();
        $allUserSkillsCount = UserSkill::count();

        $this->user->skills()->attach($skill->id, ['grade' => 1]);
        $skillIds = $this->user->skills()->pluck('skills.id')->all();
        $this->assertTrue(in_array($skill->id, $skillIds));

        $this->loginRequired('post', 'skills.my.store', ['skill_id' => $skill->id]);

        $this->actingAs($this->user)
            ->from(route('skills.my.edit', ['skill_id' => $skill->id]))
            ->put(route('skills.my.update', ['skill_id' => $skill->id]), [
                'user_skill' => [
                    'grade' => null,
                ]
            ])
            ->assertStatus(302)
            ->assertRedirect(route('skills.my.edit', ['skill_id' => $skill->id]))
            ->assertSessionHasErrors([
                'user_skill.grade' => 'Bewerten Sie ihr Können mit einer Schulnote (1-6).',
            ])
        ;

        $skillIds = $this->user->skills()->pluck('skills.id')->all();
        $this->assertTrue(in_array($skill->id, $skillIds));
    }

    public function testUpdatesAnExistingSkill()
    {
        $skill = factory(Skill::class)->create();
        $allUserSkillsCount = UserSkill::count();

        $this->user->skills()->attach($skill->id, ['grade' => 1]);
        $skillIds = $this->user->skills()->pluck('skills.id')->all();
        $this->assertTrue(in_array($skill->id, $skillIds));

        $this->loginRequired('post', 'skills.my.update', ['skill_id' => $skill->id]);

        $this->actingAs($this->user)
            ->from(route('skills.my.edit', ['skill_id' => $skill->id]))
            ->put(route('skills.my.update', ['skill_id' => $skill->id]), [
                'user_skill' => [
                    'grade' => 2,
                ]
            ])
            ->assertStatus(302)
            ->assertRedirect(route('skills.my.index'))
            ->assertSessionHas('flash_notice', 'Ihr Skill ' . $skill->name . ' wurde geändert.')
        ;

        $skillIds = $this->user->skills()->pluck('skills.id')->all();
        $this->assertTrue(in_array($skill->id, $skillIds));

        $this->assertEquals(1, $this->user->skills()->where('skill_id', $skill->id)->count());
        $userSkill = UserSkill::where('user_id', $this->user->id)->where('skill_id', $skill->id)->first();
        $this->assertEquals(2, $userSkill->grade);
    }

    public function testDeletesASkill()
    {
        $skill = factory(Skill::class)->create();

        $this->loginRequired('delete', 'skills.my.destroy', ['skill_id' => $skill->id]);

        $allUsersCount = User::count();
        $allSkillsCount = Skill::count();
        $allSkillGroupsCount = SkillGroup::count();
        $allUserSkillsCount = UserSkill::count();

        $this->actingAs($this->user)
            ->from(route('skills.my.index'))
            ->delete(route('skills.my.destroy', ['skill_id' => $skill->id]))
            ->assertStatus(302)
            ->assertRedirect(route('skills.my.index'))
            ->assertSessionHas('flash_notice', 'Ihr Skill '.$skill->name.' wurde gelöscht.')
        ;

        $this->assertEquals($allUsersCount, User::count());
        $this->assertEquals($allSkillsCount, Skill::count());
        $this->assertEquals($allSkillGroupsCount, SkillGroup::count());
        $this->assertEquals($allUserSkillsCount, UserSkill::count());
    }
}
