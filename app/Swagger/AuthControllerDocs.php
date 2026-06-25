<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Server(url: "/api", description: "GameTrackr API Server")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter JWT Bearer token to access protected endpoints"
)]
#[OA\Schema(
    schema: "User",
    type: "object",
    title: "User",
    description: "User model schema",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com"),
        new OA\Property(property: "google_id", type: "string", nullable: true, example: "1234567890"),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2026-06-25T10:00:00.000000Z"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2026-06-25T10:00:00.000000Z")
    ]
)]
class AuthControllerDocs
{
    #[OA\Post(
        path: "/auth/register",
        summary: "Register a new user",
        description: "Creates a new user account, generates an access token, and returns the newly registered user detail.",
        tags: ["Authentication"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "email", "password", "password_confirmation"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "John Doe", description: "The full name of the user"),
                new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com", description: "A unique email address"),
                new OA\Property(property: "password", type: "string", format: "password", minLength: 6, example: "secret123", description: "The password for the account (minimum 6 characters)"),
                new OA\Property(property: "password_confirmation", type: "string", format: "password", minLength: 6, example: "secret123", description: "Must match the password field")
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "User created successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "null", example: null),
                new OA\Property(property: "token", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", description: "JWT Access Token"),
                new OA\Property(property: "user", ref: "#/components/schemas/User"),
                new OA\Property(property: "message", type: "string", example: "User created successfully")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "Unauthorized")
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: "Validation Error",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "The email has already been taken."),
                new OA\Property(property: "errors", type: "object")
            ]
        )
    )]
    public function register() {}

    #[OA\Post(
        path: "/auth/login",
        summary: "User login",
        description: "Authenticates the user with email and password and returns a JWT access token.",
        tags: ["Authentication"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com"),
                new OA\Property(property: "password", type: "string", format: "password", example: "secret123")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Logged in successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "null", example: null),
                new OA\Property(property: "token", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", description: "JWT Access Token"),
                new OA\Property(property: "user", ref: "#/components/schemas/User"),
                new OA\Property(property: "message", type: "string", example: "Logged in successfully")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Invalid credentials / Unauthorized",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "Unauthorized")
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "User not found",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "User not found")
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: "Validation Error",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "The email field is required."),
                new OA\Property(property: "errors", type: "object")
            ]
        )
    )]
    public function login() {}

    #[OA\Post(
        path: "/auth/validate",
        summary: "Validate JWT Token",
        description: "Verifies if the provided JWT Token is valid and returns the current user profile.",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"]
    )]
    #[OA\Response(
        response: 200,
        description: "Token is valid",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "null", example: null),
                new OA\Property(property: "user", ref: "#/components/schemas/User")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Invalid or missing token",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "Unauthorized")
            ]
        )
    )]
    public function validateToken() {}

    #[OA\Post(
        path: "/auth/logout",
        summary: "Logout user",
        description: "Invalidates the current JWT token, logging the user out of the application.",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"]
    )]
    #[OA\Response(
        response: 200,
        description: "Logged out successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "null", example: null),
                new OA\Property(property: "message", type: "string", example: "Successfully logged out")
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Invalid or missing token",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "Unauthorized")
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: "Internal Server Error during logout",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "The token has been blacklisted")
            ]
        )
    )]
    public function logout() {}

    #[OA\Get(
        path: "/401",
        summary: "Unauthorized response fallback",
        description: "The fallback route returned for unauthenticated requests.",
        tags: ["Authentication"]
    )]
    #[OA\Response(
        response: 401,
        description: "Unauthorized request fallback",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "Unauthorized")
            ]
        )
    )]
    public function unauthorized() {}
}
