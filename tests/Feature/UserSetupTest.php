<?php

use App\Models\User;
use App\Models\UserSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

describe('User Profile Setup Management', function () {
    it('requires authentication to access setup endpoints', function () {
        $this->getJson('/api/profile/setups')->assertUnauthorized();
        $this->postJson('/api/profile/setups', ['title' => 'Test'])->assertUnauthorized();
        $this->getJson('/api/profile/setups/1')->assertUnauthorized();
        $this->putJson('/api/profile/setups/1', ['title' => 'Test'])->assertUnauthorized();
        $this->deleteJson('/api/profile/setups/1')->assertUnauthorized();
    });

    it('allows authenticated user to list their setups', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        UserSetup::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        // Another user's setup that should not appear
        $otherUser = User::factory()->create();
        UserSetup::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/setups');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'user_id', 'title', 'description', 'images', 'created_at', 'updated_at'],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(2);
    });

    it('can create a setup without images', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/setups', [
                'title' => 'My Battle Station',
                'description' => 'Custom water-cooled gaming rig with dual 4K monitors.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My Battle Station')
            ->assertJsonPath('data.description', 'Custom water-cooled gaming rig with dual 4K monitors.')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('user_setups', [
            'user_id' => $user->id,
            'title' => 'My Battle Station',
        ]);
    });

    it('can create a setup with up to 10 images', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $images = [
            UploadedFile::fake()->image('pc_front.jpg'),
            UploadedFile::fake()->image('pc_side.png'),
            UploadedFile::fake()->image('desk_setup.webp'),
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/setups', [
                'title' => 'Triple Monitor Desk',
                'description' => 'Ergonomic desk setup.',
                'images' => $images,
            ]);

        $response->assertCreated();
        $setupData = $response->json('data');

        expect($setupData['title'])->toBe('Triple Monitor Desk');
        expect($setupData['images'])->toHaveCount(3);
        expect($setupData['images'][0])->toHaveKeys(['id', 'url', 'name', 'file_name', 'mime_type', 'size']);
    });

    it('rejects creation when title is missing', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/setups', [
                'description' => 'Missing title',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    it('rejects creation when more than 10 images are uploaded', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $images = [];
        for ($i = 1; $i <= 11; $i++) {
            $images[] = UploadedFile::fake()->image("image_{$i}.jpg");
        }

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/setups', [
                'title' => 'Too Many Images',
                'images' => $images,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images']);
    });

    it('rejects non-image files on creation', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/profile/setups', [
                'title' => 'Invalid file',
                'images' => [
                    UploadedFile::fake()->create('document.pdf', 100),
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);
    });

    it('allows viewing a single setup', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $setup = UserSetup::factory()->create([
            'user_id' => $user->id,
            'title' => 'Living Room Console Setup',
            'description' => 'PS5, Switch OLED and LG C3 OLED TV.',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/profile/setups/{$setup->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $setup->id)
            ->assertJsonPath('data.title', 'Living Room Console Setup');
    });

    it('allows updating setup title and description', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $setup = UserSetup::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'description' => 'Old Description',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/profile/setups/{$setup->id}", [
                'title' => 'Updated Title',
                'description' => 'Updated Description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.description', 'Updated Description');

        $this->assertDatabaseHas('user_setups', [
            'id' => $setup->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);
    });

    it('allows updating setup via POST with multipart data and adding images', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $setup = UserSetup::factory()->create([
            'user_id' => $user->id,
            'title' => 'Sim Racing Rig',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/profile/setups/{$setup->id}", [
                'title' => 'Sim Racing Rig v2',
                'images' => [
                    UploadedFile::fake()->image('wheel.jpg'),
                    UploadedFile::fake()->image('pedals.png'),
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Sim Racing Rig v2');

        expect($response->json('data.images'))->toHaveCount(2);
    });

    it('allows removing specific existing images during update', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $setup = UserSetup::factory()->create([
            'user_id' => $user->id,
            'title' => 'Audio Setup',
        ]);

        $media1 = $setup->addMedia(UploadedFile::fake()->image('mic.jpg'))->toMediaCollection('images');
        $media2 = $setup->addMedia(UploadedFile::fake()->image('headphones.jpg'))->toMediaCollection('images');

        expect($setup->getMedia('images'))->toHaveCount(2);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/profile/setups/{$setup->id}", [
                'remove_image_ids' => [$media1->id],
            ]);

        $response->assertOk();
        $remainingImages = $response->json('data.images');
        expect($remainingImages)->toHaveCount(1);
        expect($remainingImages[0]['id'])->toBe($media2->id);
    });

    it('rejects update if total images would exceed 10', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $setup = UserSetup::factory()->create([
            'user_id' => $user->id,
            'title' => 'Full Setup',
        ]);

        for ($i = 0; $i < 8; $i++) {
            $setup->addMedia(UploadedFile::fake()->image("image_{$i}.jpg"))->toMediaCollection('images');
        }

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/profile/setups/{$setup->id}", [
                'images' => [
                    UploadedFile::fake()->image('new_1.jpg'),
                    UploadedFile::fake()->image('new_2.jpg'),
                    UploadedFile::fake()->image('new_3.jpg'),
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images']);
    });

    it('cannot update another user setup', function () {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $token = auth()->login($intruder);

        $setup = UserSetup::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Owner Setup',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/profile/setups/{$setup->id}", [
                'title' => 'Hacked Title',
            ]);

        $response->assertStatus(403);
    });

    it('allows deleting a setup and clears its media', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $setup = UserSetup::factory()->create([
            'user_id' => $user->id,
            'title' => 'Setup to delete',
        ]);
        $setup->addMedia(UploadedFile::fake()->image('temp.jpg'))->toMediaCollection('images');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/profile/setups/{$setup->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Setup deleted successfully.');

        $this->assertDatabaseMissing('user_setups', [
            'id' => $setup->id,
        ]);
    });

    it('cannot delete another user setup', function () {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $token = auth()->login($intruder);

        $setup = UserSetup::factory()->create([
            'user_id' => $owner->id,
            'title' => 'Owner Setup',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/profile/setups/{$setup->id}");

        $response->assertStatus(403);
    });

    it('allows deleting an individual image from a setup', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $setup = UserSetup::factory()->create([
            'user_id' => $user->id,
            'title' => 'Single image removal setup',
        ]);

        $media1 = $setup->addMedia(UploadedFile::fake()->image('img1.jpg'))->toMediaCollection('images');
        $media2 = $setup->addMedia(UploadedFile::fake()->image('img2.jpg'))->toMediaCollection('images');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/profile/setups/{$setup->id}/images/{$media1->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Image deleted successfully.');

        expect($response->json('data.images'))->toHaveCount(1);
        expect($response->json('data.images.0.id'))->toBe($media2->id);
    });

    it('includes setups in GET /api/profile/me', function () {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $setup = UserSetup::factory()->create([
            'user_id' => $user->id,
            'title' => 'Default Battle Station',
            'description' => 'Included in profile summary',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/profile/me');

        $response->assertOk()
            ->assertJsonPath('user.setups.0.id', $setup->id)
            ->assertJsonPath('user.setups.0.title', 'Default Battle Station');
    });
});
