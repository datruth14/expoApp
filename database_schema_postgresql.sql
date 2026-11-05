-- Delegate Experience Centre: PostgreSQL starter schema
-- Target: PostgreSQL 15+ (adapt if the organisation selects another database).
-- IMPORTANT: This is a proposed schema, not a deployed database.
-- Authentication credentials must be managed by a trusted auth provider;
-- never store plain-text passwords in these tables.

CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TYPE public.event_status AS ENUM ('draft', 'active', 'archived');
CREATE TYPE public.staff_role AS ENUM (
  'feedback_manager',
  'program_administrator',
  'system_administrator'
);

CREATE TABLE public.events (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  event_code text NOT NULL UNIQUE,
  event_name text NOT NULL,
  subtitle text,
  banner_url text,
  welcome_message text,
  start_date date,
  end_date date,
  status public.event_status NOT NULL DEFAULT 'draft',
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now(),
  CHECK (end_date IS NULL OR start_date IS NULL OR end_date >= start_date)
);

CREATE TABLE public.service_areas (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  service_code text NOT NULL UNIQUE,
  service_name text NOT NULL,
  display_order integer NOT NULL DEFAULT 0,
  is_active boolean NOT NULL DEFAULT true
);

INSERT INTO public.service_areas (service_code, service_name, display_order) VALUES
('exhibition', 'Exhibition Experience', 1),
('food_court', 'Food Court Experience', 2),
('accreditation', 'Accreditation Experience', 3),
('access_control', 'Access Control Experience', 4),
('hospitality', 'Hospitality Experience', 5),
('transportation', 'Transportation Experience', 6),
('car_parking', 'Car Parking Experience', 7),
('venue', 'Venue Experience', 8),
('lost_found', 'Lost & Found', 9)
ON CONFLICT (service_code) DO NOTHING;

-- Link staff records to the identity-provider user ID (e.g. Supabase Auth UUID).
CREATE TABLE public.staff_users (
  id uuid PRIMARY KEY,
  display_name text NOT NULL,
  email text NOT NULL UNIQUE,
  is_active boolean NOT NULL DEFAULT true,
  created_at timestamptz NOT NULL DEFAULT now(),
  updated_at timestamptz NOT NULL DEFAULT now()
);

-- A user may have more than one role. Event scope can be global or event-specific.
CREATE TABLE public.staff_role_assignments (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  staff_user_id uuid NOT NULL REFERENCES public.staff_users(id) ON DELETE CASCADE,
  role public.staff_role NOT NULL,
  event_id uuid REFERENCES public.events(id) ON DELETE CASCADE,
  created_at timestamptz NOT NULL DEFAULT now(),
  UNIQUE (staff_user_id, role, event_id)
);

CREATE TABLE public.feedback (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  event_id uuid NOT NULL REFERENCES public.events(id) ON DELETE RESTRICT,
  service_area_id uuid NOT NULL REFERENCES public.service_areas(id) ON DELETE RESTRICT,
  rating smallint NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comments text,
  is_anonymous boolean NOT NULL DEFAULT true,
  delegate_name text,
  submitted_at timestamptz NOT NULL DEFAULT now(),
  source text NOT NULL DEFAULT 'web',
  CONSTRAINT feedback_name_privacy CHECK (is_anonymous = true OR delegate_name IS NOT NULL)
);

CREATE INDEX feedback_event_date_idx ON public.feedback (event_id, submitted_at DESC);
CREATE INDEX feedback_event_service_idx ON public.feedback (event_id, service_area_id);
CREATE INDEX role_assignment_user_event_idx ON public.staff_role_assignments (staff_user_id, event_id);

CREATE TABLE public.audit_logs (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  actor_user_id uuid REFERENCES public.staff_users(id) ON DELETE SET NULL,
  event_id uuid REFERENCES public.events(id) ON DELETE SET NULL,
  action text NOT NULL,
  target_type text,
  target_id text,
  details jsonb,
  created_at timestamptz NOT NULL DEFAULT now()
);

-- Logical partitioning: every feedback row MUST carry event_id.
-- Keep each event's records isolated in queries and authorization rules.
-- For very large volumes, the developer may migrate feedback to declarative
-- RANGE/LIST partitioning after estimating volume and confirming DB support.
-- Do not create physical partitions by default without an operational need.

-- Required application/API behaviour:
-- 1. Public users may submit feedback only for an active event and active service area.
-- 2. Public users must NOT be allowed to read feedback or staff records.
-- 3. Feedback managers can read/export feedback only for assigned event(s).
-- 4. Program administrators can manage event details and service areas within
--    their assigned event(s), and manage event feedback.
-- 5. System administrators can manage all events, staff, roles and settings.
-- 6. Enforce these rules in trusted server-side API/database policies, not only UI.
-- 7. Use the auth provider's immutable user ID; do not trust a user ID supplied by
--    the browser. Validate role and event assignment on every protected request.
-- 8. Use parameterized queries, input validation, rate limiting and HTTPS.
-- 9. Do not expose service-role/database secrets in index.html or browser code.

-- Example event-scoped query for an authorized staff user:
-- SELECT f.*, s.service_name
-- FROM public.feedback f
-- JOIN public.service_areas s ON s.id = f.service_area_id
-- WHERE f.event_id = $1
-- ORDER BY f.submitted_at DESC;
