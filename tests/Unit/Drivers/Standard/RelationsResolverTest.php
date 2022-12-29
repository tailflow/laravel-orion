<?php

namespace Orion\Tests\Unit\Drivers\Standard;

use Orion\Drivers\Standard\RelationsResolver;
use Orion\Http\Requests\Request;
use Orion\Tests\Fixtures\App\Models\Category;
use Orion\Tests\Fixtures\App\Models\Company;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Unit\TestCase;

class RelationsResolverTest extends TestCase
{
    /** @test */
    public function resolving_requested_relations_with_wildcard(): void
    {
        $relationsResolver = new RelationsResolver(['user', 'editors.*', 'comments.*'], []);

        $requestedRelations = $relationsResolver->requestedRelations(
            new Request(['include' => 'user,editors.team,editors.team.users'])
        );

        self::assertSame(['user', 'editors.team', 'editors.team.users'], $requestedRelations);
    }

    /** @test */
    public function resolving_requested_relations_by_listing_nested_relations(): void
    {
        $relationsResolver = new RelationsResolver(['user', 'editors.team', 'editors.team.users'], []);

        $requestedRelations = $relationsResolver->requestedRelations(
            new Request(['include' => 'user,editors.team,editors.team.users'])
        );

        self::assertSame(['user', 'editors.team', 'editors.team.users'], $requestedRelations);
    }

    /** @test */
    public function guarding_entity_relations(): void
    {
        $post = new Post(['title' => 'test post']);
        $post->setRelations(
            [
                'user' => new User(['name' => 'manager user']),
                'editors' => collect([new User(['name' => 'editor user'])]),
            ]
        );

        $relationsResolver = new RelationsResolver(['user'], []);
        $guardedPost = $relationsResolver->guardRelations($post, ['user']);

        self::assertArrayHasKey('user', $guardedPost->getRelations());
        self::assertArrayNotHasKey('editors', $guardedPost->getRelations());
    }

    /** @test */
    public function guarding_entity_nested_relations_of_the_same_parent(): void
    {
        $user = new User(['name' => 'manager user']);
        $user->setRelations(
            [
                'company' => new Company(),
                'team' => new Team(),
            ]
        );

        $post = new Post(['title' => 'test post']);
        $post->setRelations(
            [
                'user' => $user,
            ]
        );

        $relationsResolver = new RelationsResolver([], ['user.company', 'user.team']);
        $guardedPost = $relationsResolver->guardRelations($post, ['user.company', 'user.team']);

        self::assertArrayHasKey('user', $guardedPost->getRelations());
        self::assertArrayHasKey('company', $guardedPost->getRelations()['user']->getRelations());
        self::assertArrayHasKey('team', $guardedPost->getRelations()['user']->getRelations());
    }

    /** @test */
    public function guarding_entity_nested_relations(): void
    {
        $post = new Post(['title' => 'test post']);

        $parentCategory = new Category(['name' => 'parent category']); // categories
        $childCategory = new Category(['name' => 'child category']); // categories.categories
        $nestedChildCategory = new Category(['name' => 'nested child category']); // categories.categories.categories

        $childCategory->setRelation('categories', collect([$nestedChildCategory]));
        $parentCategory->setRelation('categories', collect([$childCategory]));

        $post->setRelations(
            [
                'categories' => collect([$parentCategory]),
            ]
        );

        $relationsResolver = new RelationsResolver(['categories'], []);
        $guardedPost = $relationsResolver->guardRelations($post, ['categories']);

        self::assertArrayHasKey('categories', $guardedPost->getRelations());
        self::assertArrayHasKey('categories', $guardedPost->getRelation('categories')->first()->getRelations());
        self::assertArrayHasKey(
            'categories',
            $guardedPost->getRelation('categories')->first()->getRelation('categories')->first()->getRelations()
        );
    }

    /** @test */
    public function guarding_entity_recursive_nested_relations(): void
    {
        $post = new Post(['title' => 'test post']);

        $manager = new User(['name' => 'manager user']);

        $team = new Team(['name' => 'test team']);
        $team->setRelation('users', collect([new User(['name' => 'team user'])]));
        $team->setRelation('company', new Company(['name' => 'test company']));

        $editor = new User(['name' => 'editor user']);
        $editor->setRelation('team', $team);

        $post->setRelations(
            [
                'user' => $manager,
                'editors' => collect([$editor]),
            ]
        );

        $relationsResolver = new RelationsResolver(['user', 'editors.team.users', 'editors.team.company'], []);
        $guardedPost = $relationsResolver->guardRelations(
            $post,
            ['user', 'editors.team.users', 'editors.team.company']
        );

        self::assertArrayHasKey('user', $guardedPost->getRelations());
        self::assertArrayHasKey('editors', $guardedPost->getRelations());
        self::assertArrayHasKey('team', $guardedPost->getRelation('editors')->first()->getRelations());
        self::assertArrayHasKey('users', $guardedPost->getRelation('editors')->first()->team->getRelations());
        self::assertArrayHasKey('company', $guardedPost->getRelation('editors')->first()->team->getRelations());
    }

    /** @test */
    public function guarding_collection_relations(): void
    {
        $postA = new Post(['title' => 'test post A']);
        $postA->setRelations(
            [
                'user' => new User(['name' => 'test user']),
                'editors' => collect([new User(['name' => 'another test user'])]),
            ]
        );
        $postB = new Post(['title' => 'test post B']);
        $postB->setRelations(
            [
                'user' => new User(['name' => 'test user']),
                'editors' => collect([new User(['name' => 'another test user'])]),
            ]
        );

        $postsCollection = collect([$postA, $postB]);

        $relationsResolver = new RelationsResolver(['user'], []);
        $guardedPosts = $relationsResolver->guardRelationsForCollection($postsCollection, ['user']);

        self::assertArrayHasKey('user', $guardedPosts[0]->getRelations());
        self::assertArrayNotHasKey('editors', $guardedPosts[0]->getRelations());
        self::assertArrayHasKey('user', $guardedPosts[1]->getRelations());
        self::assertArrayNotHasKey('editors', $guardedPosts[1]->getRelations());
    }

    /** @test */
    public function resolving_relation_from_param_constraint(): void
    {
        $relationsResolver = new RelationsResolver([], []);

        $relation = $relationsResolver->relationFromParamConstraint('user.name');

        self::assertSame('user', $relation);
    }

    /** @test */
    public function resolving_deep_relation_from_param_constraint(): void
    {
        $relationsResolver = new RelationsResolver([], []);

        $relation = $relationsResolver->relationFromParamConstraint('user.posts.tags');

        self::assertSame('user.posts', $relation);
    }
}
