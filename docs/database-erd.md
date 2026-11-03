# Smoke 'n' Grill Database ERD

This ERD documents the schema defined by the project's Laravel migrations. It
includes restaurant operations, access control, and Laravel support tables.
Fields marked `PK` are primary keys; fields marked `FK` are foreign keys or
relationship keys.

## Restaurant operations

```mermaid
erDiagram
    USERS {
        bigint id PK
        bigint role_id FK
        string name
        string username UK
        string first_name
        string middle_name
        string last_name
        string suffix
        string phone
        date date_of_birth
        string gender
        string email UK
        timestamp email_verified_at
        string password
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES {
        bigint id PK
        string name
        string slug UK
        text description
        int sort_order
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    MENU_ITEMS {
        bigint id PK
        bigint category_id FK
        string name
        string slug UK
        text description
        decimal price
        string image
        boolean is_available
        boolean is_signature
        int base_prep_time
        enum station
        int stock_level
        int reorder_level
        int sort_order
        timestamp created_at
        timestamp updated_at
    }

    ORDERS {
        bigint id PK
        string order_number UK
        bigint user_id FK
        bigint reservation_id FK
        string customer_name
        string contact_number
        text delivery_address
        string payment_method
        string payment_proof
        string payment_reference
        decimal payment_amount_paid
        decimal subtotal
        decimal delivery_fee
        decimal deposit_credit
        decimal total
        string status
        int system_eta
        int final_eta
        text special_instructions
        timestamp created_at
        timestamp updated_at
    }

    ORDER_ITEMS {
        bigint id PK
        bigint order_id FK
        bigint menu_item_id FK
        string item_name
        decimal unit_price
        int quantity
        decimal line_total
        text special_instructions
        timestamp created_at
        timestamp updated_at
    }

    RESERVATIONS {
        bigint id PK
        bigint user_id FK
        bigint dining_table_id FK
        bigint applied_order_id FK
        string customer_name
        string contact_number
        string email
        int party_size
        int additional_chairs
        datetime reserved_at
        int duration_minutes
        decimal table_deposit_amount
        decimal preorder_deposit_amount
        decimal deposit_total
        string deposit_payment_method
        string deposit_payment_proof
        string deposit_payment_reference
        decimal deposit_payment_amount_paid
        string deposit_status
        timestamp deposit_paid_at
        json preorder_items
        text notes
        string status
        timestamp created_at
        timestamp updated_at
    }

    TABLE_GROUPS {
        bigint id PK
        string name
        string map_image
        int sort_order
        timestamp created_at
        timestamp updated_at
    }

    DINING_TABLES {
        bigint id PK
        bigint table_group_id FK
        string table_number
        int chair_count
        string sit_code UK
        int sort_order
        decimal map_x
        decimal map_y
        timestamp created_at
        timestamp updated_at
    }

    TABLE_OCCUPANCIES {
        bigint id PK
        bigint dining_table_id FK
        int occupied_chairs
        string status
        timestamp started_at
        timestamp ended_at
        timestamp created_at
        timestamp updated_at
    }

    RESTAURANT_SETTINGS {
        bigint id PK
        string name
        text description
        string address
        string phone
        string email
        decimal delivery_fee
        string opening_hours
        timestamp created_at
        timestamp updated_at
    }

    FEEDBACKS {
        bigint id PK
        bigint user_id FK
        bigint order_id FK
        bigint reservation_id FK
        int rating
        text comment
        string status
        bigint reviewed_by FK
        timestamp reviewed_at
        timestamp contacted_at
        bigint contacted_by FK
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES ||--o{ MENU_ITEMS : classifies
    USERS o|--o{ ORDERS : places
    RESERVATIONS o|--o{ ORDERS : linked_to
    ORDERS ||--|{ ORDER_ITEMS : contains
    MENU_ITEMS o|--o{ ORDER_ITEMS : appears_in
    USERS o|--o{ RESERVATIONS : books
    DINING_TABLES o|--o{ RESERVATIONS : reserved_for
    ORDERS o|--o{ RESERVATIONS : deposit_applied_to
    TABLE_GROUPS ||--o{ DINING_TABLES : groups
    DINING_TABLES ||--o{ TABLE_OCCUPANCIES : occupancy_history
    USERS ||--o{ FEEDBACKS : submits
    ORDERS o|--o{ FEEDBACKS : reviewed_in
    RESERVATIONS o|--o{ FEEDBACKS : reviewed_in
    USERS o|--o{ FEEDBACKS : reviews
    USERS o|--o{ FEEDBACKS : contacts
```

`RESERVATIONS.applied_order_id` and `ORDERS.reservation_id` are separate
nullable references. `TABLE_OCCUPANCIES` stores occupancy history; an active
occupancy is selected by application logic. `RESERVATIONS.preorder_items` is
JSON data, not a separate relational table. `RESTAURANT_SETTINGS` is currently
independent of the other tables.

## Access control and Laravel support

```mermaid
erDiagram
    USERS {
        bigint id PK
        bigint role_id FK
        string name
        string username UK
        string email UK
    }

    ROLES {
        bigint id PK
        string slug UK
        string name
        timestamp created_at
        timestamp updated_at
    }

    MODULES {
        bigint id PK
        string name
        string alias UK
        int sequence
        boolean is_active
        boolean sidebar_expand_children
        timestamp created_at
        timestamp updated_at
    }

    PAGES {
        bigint id PK
        bigint module_id FK
        string page_name
        string page_alias
        int sequence_number
        string icon
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    MODULE_ROLE {
        bigint id PK
        bigint module_id FK
        bigint role_id FK
        timestamp created_at
        timestamp updated_at
    }

    PAGE_ROLE {
        bigint id PK
        bigint page_id FK
        bigint role_id FK
        timestamp created_at
        timestamp updated_at
    }

    EMAIL_VERIFICATION_CODES {
        bigint id PK
        bigint user_id FK
        string code
        timestamp expires_at
        timestamp verified_at
        timestamp created_at
        timestamp updated_at
    }

    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at
    }

    SESSIONS {
        string id PK
        bigint user_id
        string ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    CACHE {
        string key PK
        mediumtext value
        int expiration
    }

    CACHE_LOCKS {
        string key PK
        string owner
        int expiration
    }

    JOBS {
        bigint id PK
        string queue
        longtext payload
        int attempts
        int reserved_at
        int available_at
        int created_at
    }

    JOB_BATCHES {
        string id PK
        string name
        int total_jobs
        int pending_jobs
        int failed_jobs
        longtext failed_job_ids
        mediumtext options
        int cancelled_at
        int created_at
        int finished_at
    }

    FAILED_JOBS {
        bigint id PK
        string uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at
    }

    MIGRATIONS {
        bigint id PK
        string migration
        int batch
    }

    ROLES o|--o{ USERS : assigned_to
    MODULES ||--o{ PAGES : contains
    MODULES ||--o{ MODULE_ROLE : access_rules
    ROLES ||--o{ MODULE_ROLE : granted_module_access
    PAGES ||--o{ PAGE_ROLE : access_rules
    ROLES ||--o{ PAGE_ROLE : granted_page_access
    USERS ||--o{ EMAIL_VERIFICATION_CODES : receives
```

The `module_role` and `page_role` tables are many-to-many pivots. Their
`role_id` (and `page_role.page_id`) columns are indexed and used as Eloquent
relationships, but the migrations do not declare database foreign-key
constraints for those columns. `users.role_id` and `sessions.user_id` are also
modeled by the application without a database foreign-key constraint.
Password-reset tokens are keyed by email and are not directly related to the
`users` table by a foreign key. Cache, queue, failed-job, and migration tables
are Laravel infrastructure tables.
