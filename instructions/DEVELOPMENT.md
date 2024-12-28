# Development Guidelines - WordPress Tidy Media Plugin

## Code Organization

### Directory Structure

```plaintext
wp-tidy-media/
├── includes/
│   ├── plugin/        # Core plugin functionality
│   ├── admin/         # Admin interface components
│   └── utilities/     # Helper functions and utilities
├── templates/         # Template files for admin views
├── assets/           # CSS, JS, and image assets
└── tests/            # Unit and integration tests
```

### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Use PHP 7.4+ features where appropriate
- Maintain PSR-4 autoloading compatibility
- Use meaningful variable and function names that reflect their purpose

### File Naming Conventions

- Use lowercase with hyphens for file names
- Class files should match class names (PSR-4)
- Template files should end with `-template.php`
- Partial templates should start with `_` (underscore)

## Development Practices

### Version Control

- Use semantic versioning (MAJOR.MINOR.PATCH)
- Keep commits atomic and well-described
- Branch naming:
  - `feature/` for new features
  - `fix/` for bug fixes
  - `refactor/` for code improvements
  - `docs/` for documentation updates

### Documentation

- Maintain PHPDoc blocks for all classes and methods
- Document any changes to the database schema
- Update README.md for new features
- Include code examples for hooks and filters

### Testing

- Write unit tests for new functionality
- Test across multiple WordPress versions
- Verify compatibility with popular themes and plugins
- Test with different PHP versions (7.4+)

## WordPress Integration

### Hooks and Filters

- Prefix all hooks with `cwp_tidy_media_`
- Document all hooks in `hooks.md`
- Maintain backward compatibility when modifying existing hooks
- Example format:

  ```php
  do_action('cwp_tidy_media_before_reorganize', $post_id);
  apply_filters('cwp_tidy_media_file_path', $path, $post_id);
  ```

### Database Operations

- Use `$wpdb` prepared statements
- Maintain upgrade routines in `includes/plugin/class-upgrades.php`
- Document schema changes in `schema.md`
- Include rollback capabilities

### Security Practices

- Sanitize all input data
- Escape all output
- Use nonces for form submissions
- Verify user capabilities
- Follow WordPress security best practices

## Performance Considerations

### Optimization Guidelines

- Cache expensive operations
- Use transients for temporary data
- Implement batch processing for bulk operations
- Minimize database queries
- Profile code performance regularly

### Resource Management

- Clean up temporary files
- Implement garbage collection for orphaned data
- Monitor memory usage in bulk operations
- Use WordPress cron for scheduled tasks

## UI/UX Guidelines

### Admin Interface

- Follow WordPress admin design patterns
- Use WordPress UI components
- Implement responsive design
- Provide clear error messages and success notifications

### Accessibility

- Follow WCAG 2.1 guidelines
- Use semantic HTML
- Maintain keyboard navigation
- Include ARIA labels where necessary

## Error Handling

### Logging

- Use WordPress debug log for development
- Implement plugin-specific logging
- Log levels:
  - ERROR: Failed operations
  - WARNING: Potential issues
  - INFO: Successful operations
  - DEBUG: Development information

### Error Messages

- User-friendly messages for admin interface
- Technical details in logs
- Actionable error resolution steps
- Multilingual support for error messages

## API Guidelines

### Internal APIs

- Maintain consistent method signatures
- Document method parameters and return types
- Use type hints where possible
- Example:

  ```php
  /**
   * Reorganize media files for a post
   *
   * @param int    $post_id The post ID
   * @param array  $options Organization options
   * @return bool|WP_Error True on success, WP_Error on failure
   */
  public function reorganize_media(int $post_id, array $options = []): bool|WP_Error
  ```

### External APIs

- Version all public APIs
- Maintain backward compatibility
- Document breaking changes
- Provide migration guides

## Deployment

### Release Checklist

1. Update version numbers
2. Run full test suite
3. Update changelog
4. Generate documentation
5. Create release notes
6. Tag release in repository
7. Build production assets
8. Verify WordPress compatibility

### Quality Assurance

- Code review requirements
- Testing environments
- Performance benchmarks
- Security scanning
- Compatibility testing

## Support and Maintenance

### Issue Management

- Use GitHub issues for tracking
- Label issues appropriately
- Prioritize security fixes
- Maintain issue templates

### Community Guidelines

- Code of conduct
- Contributing guidelines
- Pull request templates
- Documentation standards

## Future Development

### Roadmap Planning

- Maintain feature roadmap
- Version compatibility planning
- Deprecation schedules
- Migration strategies

### Technical Debt

- Regular code reviews
- Refactoring priorities
- Legacy code handling
- Performance optimization goals
