<?php

namespace Pmochine\LaravelTongue\Tests\Unit;

use Illuminate\Contracts\Routing\UrlRoutable;
use PHPUnit\Framework\Attributes\Test;
use Pmochine\LaravelTongue\Accent\Accent;
use Pmochine\LaravelTongue\Tests\Fixtures\Post;
use Pmochine\LaravelTongue\Tests\TestCase;

class AccentTest extends TestCase
{
    #[Test]
    public function it_substitutes_scalar_attributes()
    {
        $this->assertEquals('hallo/samplename', Accent::substituteAttributesInRoute(['username' => 'samplename'], 'hallo/{username}'));
        $this->assertEquals('page/2', Accent::substituteAttributesInRoute(['number' => 2], 'page/{number?}'));
    }

    #[Test]
    public function it_skips_array_attributes_like_the_defaults_of_route_view()
    {
        $attributes = ['view' => 'privacy', 'data' => [], 'status' => 200, 'headers' => []];

        $this->assertEquals('privacy', Accent::substituteAttributesInRoute($attributes, 'privacy'));
    }

    #[Test]
    public function it_uses_the_route_key_of_bound_models()
    {
        $user = new class implements UrlRoutable
        {
            public function getRouteKey()
            {
                return 'samplename';
            }

            public function getRouteKeyName()
            {
                return 'username';
            }

            public function resolveRouteBinding($value, $field = null)
            {
                return null;
            }

            public function resolveChildRouteBinding($childType, $value, $field)
            {
                return null;
            }

            public function __toString()
            {
                return '{"username":"samplename"}';
            }
        };

        $this->assertEquals('hallo/samplename', Accent::substituteAttributesInRoute(['username' => $user], 'hallo/{username}'));
    }

    #[Test]
    public function it_uses_the_value_of_backed_enums()
    {
        $this->assertEquals('sort/asc', Accent::substituteAttributesInRoute(['order' => AccentTestOrder::Asc], 'sort/{order}'));
    }

    #[Test]
    public function it_uses_the_binding_field_of_a_placeholder_or_of_the_route()
    {
        $this->assertEquals('posts/hello-world', Accent::substituteAttributesInRoute(['post' => new Post], 'posts/{post:slug}'));
        $this->assertEquals('posts/hello-world', Accent::substituteAttributesInRoute(['post' => new Post], 'posts/{post}', ['post' => 'slug']));
        $this->assertEquals('posts/123', Accent::substituteAttributesInRoute(['post' => new Post], 'posts/{post}'));
    }

    #[Test]
    public function it_encodes_the_attributes_for_the_path()
    {
        $this->assertEquals('hallo/a%23b%3Fc%25d%20e', Accent::substituteAttributesInRoute(['username' => 'a#b?c%d e'], 'hallo/{username}'));
        $this->assertEquals('files/a/b@c', Accent::substituteAttributesInRoute(['file' => 'a/b@c'], 'files/{file}'));
    }

    #[Test]
    public function it_removes_missing_optional_parameters_without_attributes()
    {
        $this->assertEquals('blog', Accent::substituteAttributesInRoute([], 'blog/{page?}'));
        $this->assertEquals('', Accent::substituteAttributesInRoute([], '{page?}'));
        $this->assertEquals('hallo/{username}', Accent::substituteAttributesInRoute([], 'hallo/{username}'));
    }

    #[Test]
    public function it_removes_only_the_optional_parameters_that_are_missing()
    {
        $this->assertEquals('user/5/posts', Accent::substituteAttributesInRoute(['id' => 5], 'user/{id}/posts/{page?}'));
        $this->assertEquals('user/posts/2', Accent::substituteAttributesInRoute(['page' => 2], 'user/{id?}/posts/{page?}'));
        $this->assertEquals('user/posts', Accent::substituteAttributesInRoute(['lang' => 'de'], 'user/{id?}/posts/{page?}'));
    }
}

enum AccentTestOrder: string
{
    case Asc = 'asc';
}
