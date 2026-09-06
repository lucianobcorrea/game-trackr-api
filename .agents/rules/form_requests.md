# Form Requests Rule

Always extract request validation logic out of Controllers into dedicated FormRequest classes located in `app/Http/Requests`.

- Do not use inline `$request->validate([...])` inside Controller methods.
- Create custom FormRequest classes for create/update endpoints (e.g. `UpdateProfileRequest`, `CreateUserRequest`, `CreatePostRequest`).
- Typehint the FormRequest in Controller method signatures and call `$request->validated()` to get validated inputs.
