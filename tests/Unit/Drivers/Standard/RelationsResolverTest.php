<?php

namespace Orion\Tests\Unit\Drivers\Standard;

use Orion\Drivers\Standard\RelationsResolver;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Models\Team;
use Orion\Tests\Fixtures\App\Models\User;
use Orion\Tests\Unit\TestCase;

class RelationsResolverTest extends TestCase
{
    /** @test */
    public function guarding_entity_relations()
    {
        $post = new Post(['title' => 'test post']);
        $post->setRelations([
            'user' => new User(['name' => 'manager user']),
            'editors' => collect([new User(['name' => 'editor user'])])
        ]);

        $relationsResolver = new RelationsResolver(['user'], []);
        $guardedPost = $relationsResolver->guardRelations($post, ['user']);

        self::assertArrayHasKey('user', $guardedPost->getRelations());
        self::assertArrayNotHasKey('editors', $guardedPost->getRelations());
    }

    /** @test */
    public function guarding_entity_nested_relations()
    {
        $post = new Post(['title' => 'test post']);

        $manager = new User(['name' => 'manager user']);
        $editor = new User(['name' => 'editor user']);
        $editor->setRelation('team', new Team(['name' => 'test team']));

        $post->setRelations([
            'user' => $manager,
            'editors' => collect([$editor])
        ]);

        $relationsResolver = new RelationsResolver(['user', 'editors.team'], []);
        $guardedPost = $relationsResolver->guardRelations($post, ['user', 'editors.team']);

        self::assertArrayHasKey('user', $guardedPost->getRelations());
        self::assertArrayHasKey('editors', $guardedPost->getRelations());
        self::assertArrayHasKey('team', $guardedPost->getRelation('editors')->first()->getRelations());
    }

    /** @test */
    public function guarding_collection_relations()
    {
        $postA = new Post(['title' => 'test post A']);
        $postA->setRelations([
            'user' => new User(['name' => 'test user']),
            'editors' => collect([new User(['name' => 'another test user'])])
        ]);
        $postB = new Post(['title' => 'test post B']);
        $postB->setRelations([
            'user' => new User(['name' => 'test user']),
            'editors' => collect([new User(['name' => 'another test user'])])
        ]);

        $postsCollection = collect([$postA, $postB]);

        $relationsResolver = new RelationsResolver(['user'], []);
        $guardedPosts = $relationsResolver->guardRelationsForCollection($postsCollection, ['user']);

        self::assertArrayHasKey('user', $guardedPosts[0]->getRelations());
        self::assertArrayNotHasKey('editors', $guardedPosts[0]->getRelations());
        self::assertArrayHasKey('user', $guardedPosts[1]->getRelations());
        self::assertArrayNotHasKey('editors', $guardedPosts[1]->getRelations());
    }
}