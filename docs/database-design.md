# SocialX Database Design

## Database

MySQL 8

## Domains

### Identity

- users
- user_settings
- roles
- permissions
- user_roles

### Content

- posts
- post_media

### Engagement

- likes
- reposts
- bookmarks

### Social Graph

- follows
- follow_requests
- blocks
- mutes

### Discovery

- hashtags
- post_hashtags
- mentions

### Notifications

- notifications

### Messaging

- conversations
- conversation_members
- messages
- message_attachments

### Moderation

- reports

## Key Constraints

- users.username UNIQUE
- users.email UNIQUE
- user_settings.user_id UNIQUE
- likes(user_id, post_id) UNIQUE
- reposts(user_id, post_id) UNIQUE
- bookmarks(user_id, post_id) UNIQUE
- follows(follower_id, following_id) UNIQUE
- blocks(blocker_id, blocked_id) UNIQUE
- mutes(muter_id, muted_id) UNIQUE
- post_hashtags(post_id, hashtag_id) UNIQUE
- mentions(post_id, mentioned_user_id) UNIQUE
- conversation_members(conversation_id, user_id) UNIQUE

## Important Indexes

- posts(user_id, created_at)
- posts(root_post_id, created_at)
- likes(post_id, created_at)
- follows(following_id, created_at)
- notifications(user_id, created_at)
- notifications(user_id, read_at)
- messages(conversation_id, id)