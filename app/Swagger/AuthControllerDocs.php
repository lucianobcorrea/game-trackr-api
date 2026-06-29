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

    #[OA\Post(
        path: "/auth/refresh",
        summary: "Refresh JWT Token",
        description: "Refreshes the current JWT token and returns a new access token.",
        security: [["bearerAuth" => []]],
        tags: ["Authentication"]
    )]
    #[OA\Response(
        response: 200,
        description: "Token refreshed successfully",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "token", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", description: "New JWT Access Token")
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
    public function refresh() {}

    #[OA\Post(
        path: "/auth/forgot-password",
        summary: "Send password reset link or verification code",
        description: "Sends a password reset link (web client) or a 6-digit verification code (mobile client) to the user's email address if it exists.",
        tags: ["Authentication"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com", description: "The email address of the user"),
                new OA\Property(property: "client", type: "string", enum: ["web", "mobile"], default: "web", example: "mobile", description: "The client type requesting the reset")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Instructions sent if the email exists",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "If the email exists, instructions were sent.")
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
    public function forgotPassword() {}

    #[OA\Post(
        path: "/auth/reset-password",
        summary: "Reset user password",
        description: "Resets the user's password using either the reset token (web client) or verification code (mobile client).",
        tags: ["Authentication"]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password", "password_confirmation"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com"),
                new OA\Property(property: "password", type: "string", format: "password", minLength: 8, example: "newsecret123"),
                new OA\Property(property: "password_confirmation", type: "string", format: "password", minLength: 8, example: "newsecret123"),
                new OA\Property(property: "client", type: "string", enum: ["web", "mobile"], default: "web", example: "mobile", description: "The client type resetting the password"),
                new OA\Property(property: "token", type: "string", example: "example-reset-token", description: "Required for web client: The reset token sent via email link"),
                new OA\Property(property: "code", type: "string", example: "123456", description: "Required for mobile client: The 6-digit code sent via email")
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Password reset successful",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", nullable: true, example: null),
                new OA\Property(property: "message", type: "string", example: "Password reset successful")
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Invalid, expired, or already used verification code (mobile client only)",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "Invalid code")
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: "Validation Error",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "message", type: "string", example: "The password field is required."),
                new OA\Property(property: "errors", type: "object")
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: "Password reset failed (web client only)",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "error", type: "string", example: "Password reset failed")
            ]
        )
    )]
    public function resetPassword() {}

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
