--
-- PostgreSQL database dump
--

-- Dumped from database version 13.21 (Debian 13.21-1.pgdg120+1)
-- Dumped by pg_dump version 13.21 (Debian 13.21-1.pgdg120+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: batch_delete_expired_rows(); Type: FUNCTION; Schema: public; Owner: kong
--

CREATE FUNCTION public.batch_delete_expired_rows() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
        BEGIN
          EXECUTE FORMAT('WITH rows AS (SELECT ctid FROM %s WHERE %s < CURRENT_TIMESTAMP AT TIME ZONE ''UTC'' ORDER BY %s LIMIT 2 FOR UPDATE SKIP LOCKED) DELETE FROM %s WHERE ctid IN (TABLE rows)', TG_TABLE_NAME, TG_ARGV[0], TG_ARGV[0], TG_TABLE_NAME);
          RETURN NULL;
        END;
      $$;


ALTER FUNCTION public.batch_delete_expired_rows() OWNER TO kong;

--
-- Name: batch_delete_expired_rows_and_gen_deltas(); Type: FUNCTION; Schema: public; Owner: kong
--

CREATE FUNCTION public.batch_delete_expired_rows_and_gen_deltas() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
          DECLARE
            -- SQL query to fetch expired records
            query_expired_record TEXT;

            -- record to store the expired record
            expired_record RECORD;

            -- new version to be inserted in clustering_sync_version
            -- which associated with the generated delta that
            -- deletes the expired record
            new_version integer;

            -- default_ws_id to be used in the delta
            default_ws_id UUID;

            -- unused variable to acquire lock on clustering_sync_lock
            unused_var integer;
          BEGIN
            -- %1$I means consider the first argument as an database identifier like table name
            -- %2$I does the same for the second argument

            -- has ws_id ?
            IF (SELECT TG_ARGV[2]) THEN
              query_expired_record := FORMAT('
                SELECT id, ws_id
                FROM %1$I
                WHERE %2$I < CURRENT_TIMESTAMP AT TIME ZONE ''UTC''
                ORDER BY %2$I
                LIMIT 2
                FOR UPDATE SKIP LOCKED
              ', TG_TABLE_NAME, TG_ARGV[0]);
            ELSE
              query_expired_record := FORMAT('
                SELECT id
                FROM %1$I
                WHERE %2$I < CURRENT_TIMESTAMP AT TIME ZONE ''UTC''
                ORDER BY %2$I
                LIMIT 2
                FOR UPDATE SKIP LOCKED
              ', TG_TABLE_NAME, TG_ARGV[0]);
            END IF;

            SELECT id INTO default_ws_id FROM workspaces WHERE name = 'default';

            FOR expired_record IN EXECUTE query_expired_record LOOP
              -- %2$L means consider the second argument as a literal value,
              -- such as add quotes around TEXT
              EXECUTE FORMAT('
                DELETE FROM %1$I
                WHERE id = %2$L
              ', TG_TABLE_NAME, expired_record.id);

              SELECT id INTO unused_var FROM clustering_sync_lock FOR UPDATE;
              INSERT INTO clustering_sync_version DEFAULT VALUES RETURNING version INTO new_version;

              -- has ws_id ?
              IF (SELECT TG_ARGV[2]) THEN
                INSERT INTO clustering_sync_delta (version, type, pk, ws_id, entity)
                VALUES (new_version, TG_ARGV[1], json_build_object('id', expired_record.id), expired_record.ws_id, null);
              ELSE
                INSERT INTO clustering_sync_delta (version, type, pk, ws_id, entity)
                VALUES (new_version, TG_ARGV[1], json_build_object('id', expired_record.id), default_ws_id, null);
              END IF;

              UPDATE clustering_sync_lock SET id=1;
            END LOOP;

            RETURN NULL;
          END;
        $_$;


ALTER FUNCTION public.batch_delete_expired_rows_and_gen_deltas() OWNER TO kong;

--
-- Name: sync_tags(); Type: FUNCTION; Schema: public; Owner: kong
--

CREATE FUNCTION public.sync_tags() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
        BEGIN
          IF (TG_OP = 'TRUNCATE') THEN
            DELETE FROM tags WHERE entity_name = TG_TABLE_NAME;
            RETURN NULL;
          ELSIF (TG_OP = 'DELETE') THEN
            DELETE FROM tags WHERE entity_id = OLD.id;
            RETURN OLD;
          ELSE

          -- Triggered by INSERT/UPDATE
          -- Do an upsert on the tags table
          -- So we don't need to migrate pre 1.1 entities
          INSERT INTO tags VALUES (NEW.id, TG_TABLE_NAME, NEW.tags)
          ON CONFLICT (entity_id) DO UPDATE
                  SET tags=EXCLUDED.tags;
          END IF;
          RETURN NEW;
        END;
      $$;


ALTER FUNCTION public.sync_tags() OWNER TO kong;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: acls; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.acls (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid,
    "group" text,
    cache_key text,
    tags text[],
    ws_id uuid
);


ALTER TABLE public.acls OWNER TO kong;

--
-- Name: acme_storage; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.acme_storage (
    id uuid NOT NULL,
    key text,
    value text,
    created_at timestamp with time zone,
    ttl timestamp with time zone
);


ALTER TABLE public.acme_storage OWNER TO kong;

--
-- Name: admins; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.admins (
    id uuid NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid,
    rbac_user_id uuid,
    rbac_token_enabled boolean NOT NULL,
    email text,
    status integer,
    username text,
    custom_id text,
    username_lower text
);


ALTER TABLE public.admins OWNER TO kong;

--
-- Name: application_instances; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.application_instances (
    id uuid NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    status integer,
    service_id uuid,
    application_id uuid,
    composite_id text,
    suspended boolean NOT NULL,
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid
);


ALTER TABLE public.application_instances OWNER TO kong;

--
-- Name: applications; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.applications (
    id uuid NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    name text,
    description text,
    redirect_uri text,
    meta text,
    developer_id uuid,
    consumer_id uuid,
    custom_id text,
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid
);


ALTER TABLE public.applications OWNER TO kong;

--
-- Name: audit_objects; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.audit_objects (
    id uuid NOT NULL,
    request_id character(32),
    entity_key uuid,
    dao_name text NOT NULL,
    operation character(6) NOT NULL,
    entity text,
    rbac_user_id uuid,
    signature text,
    ttl timestamp with time zone DEFAULT (timezone('utc'::text, CURRENT_TIMESTAMP(0)) + '720:00:00'::interval),
    removed_from_entity text,
    request_timestamp timestamp without time zone DEFAULT timezone('utc'::text, CURRENT_TIMESTAMP(3))
);


ALTER TABLE public.audit_objects OWNER TO kong;

--
-- Name: audit_requests; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.audit_requests (
    request_id character(32) NOT NULL,
    request_timestamp timestamp without time zone DEFAULT timezone('utc'::text, CURRENT_TIMESTAMP(3)),
    client_ip text NOT NULL,
    path text NOT NULL,
    method text NOT NULL,
    payload text,
    status integer NOT NULL,
    rbac_user_id uuid,
    workspace uuid,
    signature text,
    ttl timestamp with time zone DEFAULT (timezone('utc'::text, CURRENT_TIMESTAMP(0)) + '720:00:00'::interval),
    removed_from_payload text,
    rbac_user_name text,
    request_source text
);


ALTER TABLE public.audit_requests OWNER TO kong;

--
-- Name: basicauth_credentials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.basicauth_credentials (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid,
    username text,
    password text,
    tags text[],
    ws_id uuid
);


ALTER TABLE public.basicauth_credentials OWNER TO kong;

--
-- Name: ca_certificates; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.ca_certificates (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    cert text NOT NULL,
    tags text[],
    cert_digest text NOT NULL,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.ca_certificates OWNER TO kong;

--
-- Name: certificates; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.certificates (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    cert text,
    key text,
    tags text[],
    ws_id uuid,
    cert_alt text,
    key_alt text,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.certificates OWNER TO kong;

--
-- Name: cluster_events; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.cluster_events (
    id uuid NOT NULL,
    node_id uuid NOT NULL,
    at timestamp with time zone NOT NULL,
    nbf timestamp with time zone,
    expire_at timestamp with time zone NOT NULL,
    channel text,
    data text
);


ALTER TABLE public.cluster_events OWNER TO kong;

--
-- Name: clustering_data_planes; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.clustering_data_planes (
    id uuid NOT NULL,
    hostname text NOT NULL,
    ip text NOT NULL,
    last_seen timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    config_hash text NOT NULL,
    ttl timestamp with time zone,
    version text,
    sync_status text DEFAULT 'unknown'::text NOT NULL,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    labels jsonb,
    cert_details jsonb,
    rpc_capabilities text[]
);


ALTER TABLE public.clustering_data_planes OWNER TO kong;

--
-- Name: clustering_rpc_requests; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.clustering_rpc_requests (
    id bigint NOT NULL,
    node_id uuid NOT NULL,
    reply_to uuid NOT NULL,
    ttl timestamp with time zone NOT NULL,
    payload json NOT NULL
);


ALTER TABLE public.clustering_rpc_requests OWNER TO kong;

--
-- Name: clustering_rpc_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: kong
--

CREATE SEQUENCE public.clustering_rpc_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.clustering_rpc_requests_id_seq OWNER TO kong;

--
-- Name: clustering_rpc_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: kong
--

ALTER SEQUENCE public.clustering_rpc_requests_id_seq OWNED BY public.clustering_rpc_requests.id;


--
-- Name: clustering_sync_delta; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.clustering_sync_delta (
    version bigint NOT NULL,
    type text NOT NULL,
    pk json NOT NULL,
    ws_id uuid NOT NULL,
    entity json
);


ALTER TABLE public.clustering_sync_delta OWNER TO kong;

--
-- Name: clustering_sync_lock; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.clustering_sync_lock (
    id integer NOT NULL
);


ALTER TABLE public.clustering_sync_lock OWNER TO kong;

--
-- Name: clustering_sync_version; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.clustering_sync_version (
    version bigint NOT NULL
);


ALTER TABLE public.clustering_sync_version OWNER TO kong;

--
-- Name: clustering_sync_version_version_seq; Type: SEQUENCE; Schema: public; Owner: kong
--

CREATE SEQUENCE public.clustering_sync_version_version_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.clustering_sync_version_version_seq OWNER TO kong;

--
-- Name: clustering_sync_version_version_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: kong
--

ALTER SEQUENCE public.clustering_sync_version_version_seq OWNED BY public.clustering_sync_version.version;


--
-- Name: consumer_group_consumers; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.consumer_group_consumers (
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_group_id uuid NOT NULL,
    consumer_id uuid NOT NULL,
    cache_key text,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.consumer_group_consumers OWNER TO kong;

--
-- Name: consumer_group_plugins; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.consumer_group_plugins (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_group_id uuid,
    name text NOT NULL,
    cache_key text,
    config jsonb NOT NULL,
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.consumer_group_plugins OWNER TO kong;

--
-- Name: consumer_groups; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.consumer_groups (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    name text,
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid,
    tags text[],
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.consumer_groups OWNER TO kong;

--
-- Name: consumer_reset_secrets; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.consumer_reset_secrets (
    id uuid NOT NULL,
    consumer_id uuid,
    secret text,
    status integer,
    client_addr text,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp without time zone DEFAULT timezone('utc'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.consumer_reset_secrets OWNER TO kong;

--
-- Name: consumers; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.consumers (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    username text,
    custom_id text,
    tags text[],
    ws_id uuid,
    username_lower text,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    type integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.consumers OWNER TO kong;

--
-- Name: credentials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.credentials (
    id uuid NOT NULL,
    consumer_id uuid,
    consumer_type integer,
    plugin text NOT NULL,
    credential_data json,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, ('now'::text)::timestamp(0) with time zone),
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.credentials OWNER TO kong;

--
-- Name: custom_plugins; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.custom_plugins (
    id uuid NOT NULL,
    ws_id uuid,
    name text NOT NULL,
    schema text NOT NULL,
    handler text NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp with time zone,
    tags text[]
);


ALTER TABLE public.custom_plugins OWNER TO kong;

--
-- Name: degraphql_routes; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.degraphql_routes (
    id uuid NOT NULL,
    service_id uuid,
    methods text[],
    uri text,
    query text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.degraphql_routes OWNER TO kong;

--
-- Name: developers; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.developers (
    id uuid NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    email text,
    status integer,
    meta text,
    custom_id text,
    consumer_id uuid,
    rbac_user_id uuid,
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid
);


ALTER TABLE public.developers OWNER TO kong;

--
-- Name: document_objects; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.document_objects (
    id uuid NOT NULL,
    created_at timestamp without time zone,
    updated_at timestamp without time zone,
    service_id uuid,
    path text,
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid
);


ALTER TABLE public.document_objects OWNER TO kong;

--
-- Name: event_hooks; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.event_hooks (
    id uuid,
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    source text NOT NULL,
    event text,
    handler text NOT NULL,
    on_change boolean,
    snooze integer,
    config json NOT NULL,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.event_hooks OWNER TO kong;

--
-- Name: files; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.files (
    id uuid NOT NULL,
    path text NOT NULL,
    checksum text,
    contents text,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, CURRENT_TIMESTAMP(0)),
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.files OWNER TO kong;

--
-- Name: filter_chains; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.filter_chains (
    id uuid NOT NULL,
    name text,
    enabled boolean DEFAULT true,
    route_id uuid,
    service_id uuid,
    ws_id uuid,
    cache_key text,
    filters jsonb[],
    tags text[],
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.filter_chains OWNER TO kong;

--
-- Name: graphql_ratelimiting_advanced_cost_decoration; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.graphql_ratelimiting_advanced_cost_decoration (
    id uuid NOT NULL,
    service_id uuid,
    type_path text,
    add_arguments text[],
    add_constant double precision,
    mul_arguments text[],
    mul_constant double precision,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.graphql_ratelimiting_advanced_cost_decoration OWNER TO kong;

--
-- Name: group_rbac_roles; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.group_rbac_roles (
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    group_id uuid NOT NULL,
    rbac_role_id uuid NOT NULL,
    workspace_id uuid,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.group_rbac_roles OWNER TO kong;

--
-- Name: groups; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.groups (
    id uuid NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    name text,
    comment text,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.groups OWNER TO kong;

--
-- Name: header_cert_auth_credentials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.header_cert_auth_credentials (
    id uuid NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid NOT NULL,
    subject_name text NOT NULL,
    ca_certificate_id uuid,
    cache_key text,
    tags text[],
    ws_id uuid
);


ALTER TABLE public.header_cert_auth_credentials OWNER TO kong;

--
-- Name: hmacauth_credentials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.hmacauth_credentials (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid,
    username text,
    secret text,
    tags text[],
    ws_id uuid
);


ALTER TABLE public.hmacauth_credentials OWNER TO kong;

--
-- Name: jwt_secrets; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.jwt_secrets (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid,
    key text,
    secret text,
    algorithm text,
    rsa_public_key text,
    tags text[],
    ws_id uuid
);


ALTER TABLE public.jwt_secrets OWNER TO kong;

--
-- Name: jwt_signer_jwks; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.jwt_signer_jwks (
    id uuid NOT NULL,
    name text NOT NULL,
    keys jsonb[] NOT NULL,
    previous jsonb[],
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.jwt_signer_jwks OWNER TO kong;

--
-- Name: key_sets; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.key_sets (
    id uuid NOT NULL,
    name text,
    tags text[],
    ws_id uuid,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


ALTER TABLE public.key_sets OWNER TO kong;

--
-- Name: keyauth_credentials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.keyauth_credentials (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid,
    key text,
    tags text[],
    ttl timestamp with time zone,
    ws_id uuid
);


ALTER TABLE public.keyauth_credentials OWNER TO kong;

--
-- Name: keyauth_enc_credentials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.keyauth_enc_credentials (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid,
    key text,
    key_ident text,
    ws_id uuid,
    tags text[],
    ttl timestamp with time zone
);


ALTER TABLE public.keyauth_enc_credentials OWNER TO kong;

--
-- Name: keyring_keys; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.keyring_keys (
    id text NOT NULL,
    recovery_key_id text NOT NULL,
    key_encrypted text NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL
);


ALTER TABLE public.keyring_keys OWNER TO kong;

--
-- Name: keyring_meta; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.keyring_meta (
    id text NOT NULL,
    state text NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.keyring_meta OWNER TO kong;

--
-- Name: keys; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.keys (
    id uuid NOT NULL,
    set_id uuid,
    name text,
    cache_key text,
    ws_id uuid,
    kid text,
    jwk text,
    pem jsonb,
    tags text[],
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    x5t text
);


ALTER TABLE public.keys OWNER TO kong;

--
-- Name: konnect_applications; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.konnect_applications (
    id uuid NOT NULL,
    ws_id uuid,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    client_id text,
    scopes text[],
    tags text[],
    consumer_groups text[],
    auth_strategy_id text,
    application_context jsonb,
    exhausted_scopes text[]
);


ALTER TABLE public.konnect_applications OWNER TO kong;

--
-- Name: legacy_files; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.legacy_files (
    id uuid NOT NULL,
    auth boolean NOT NULL,
    name text NOT NULL,
    type text NOT NULL,
    contents text,
    created_at timestamp without time zone DEFAULT timezone('utc'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.legacy_files OWNER TO kong;

--
-- Name: license_data; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.license_data (
    node_id uuid NOT NULL,
    req_cnt bigint,
    license_creation_date timestamp without time zone,
    year smallint NOT NULL,
    month smallint NOT NULL
);


ALTER TABLE public.license_data OWNER TO kong;

--
-- Name: license_llm_data; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.license_llm_data (
    id uuid NOT NULL,
    model_name text NOT NULL,
    license_creation_date timestamp without time zone,
    year smallint NOT NULL,
    week_of_year smallint NOT NULL
);


ALTER TABLE public.license_llm_data OWNER TO kong;

--
-- Name: licenses; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.licenses (
    id uuid NOT NULL,
    payload text NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    checksum text
);


ALTER TABLE public.licenses OWNER TO kong;

--
-- Name: locks; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.locks (
    key text NOT NULL,
    owner text,
    ttl timestamp with time zone
);


ALTER TABLE public.locks OWNER TO kong;

--
-- Name: login_attempts; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.login_attempts (
    consumer_id uuid NOT NULL,
    attempts json DEFAULT '{}'::json,
    ttl timestamp with time zone,
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    attempt_type text DEFAULT 'login'::text NOT NULL
);


ALTER TABLE public.login_attempts OWNER TO kong;

--
-- Name: mtls_auth_credentials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.mtls_auth_credentials (
    id uuid NOT NULL,
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    consumer_id uuid NOT NULL,
    subject_name text NOT NULL,
    ca_certificate_id uuid,
    cache_key text,
    ws_id uuid,
    tags text[]
);


ALTER TABLE public.mtls_auth_credentials OWNER TO kong;

--
-- Name: oauth2_authorization_codes; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.oauth2_authorization_codes (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    credential_id uuid,
    service_id uuid,
    code text,
    authenticated_userid text,
    scope text,
    ttl timestamp with time zone,
    challenge text,
    challenge_method text,
    ws_id uuid,
    plugin_id uuid
);


ALTER TABLE public.oauth2_authorization_codes OWNER TO kong;

--
-- Name: oauth2_credentials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.oauth2_credentials (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    name text,
    consumer_id uuid,
    client_id text,
    client_secret text,
    redirect_uris text[],
    tags text[],
    client_type text,
    hash_secret boolean,
    ws_id uuid
);


ALTER TABLE public.oauth2_credentials OWNER TO kong;

--
-- Name: oauth2_tokens; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.oauth2_tokens (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    credential_id uuid,
    service_id uuid,
    access_token text,
    refresh_token text,
    token_type text,
    expires_in integer,
    authenticated_userid text,
    scope text,
    ttl timestamp with time zone,
    ws_id uuid
);


ALTER TABLE public.oauth2_tokens OWNER TO kong;

--
-- Name: oic_issuers; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.oic_issuers (
    id uuid NOT NULL,
    issuer text,
    configuration text,
    keys text,
    secret text,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.oic_issuers OWNER TO kong;

--
-- Name: oic_jwks; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.oic_jwks (
    id uuid NOT NULL,
    jwks jsonb
);


ALTER TABLE public.oic_jwks OWNER TO kong;

--
-- Name: parameters; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.parameters (
    key text NOT NULL,
    value text NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.parameters OWNER TO kong;

--
-- Name: partials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.partials (
    id uuid,
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    type text NOT NULL,
    config json NOT NULL,
    ws_id uuid,
    name text,
    tags text[]
);


ALTER TABLE public.partials OWNER TO kong;

--
-- Name: plugins; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.plugins (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    name text NOT NULL,
    consumer_id uuid,
    service_id uuid,
    route_id uuid,
    config jsonb NOT NULL,
    enabled boolean NOT NULL,
    cache_key text,
    protocols text[],
    tags text[],
    ws_id uuid,
    instance_name text,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    ordering jsonb,
    consumer_group_id uuid
);


ALTER TABLE public.plugins OWNER TO kong;

--
-- Name: plugins_partials; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.plugins_partials (
    id uuid,
    created_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp without time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    path text,
    plugin_id uuid,
    partial_id uuid
);


ALTER TABLE public.plugins_partials OWNER TO kong;

--
-- Name: ratelimiting_metrics; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.ratelimiting_metrics (
    identifier text NOT NULL,
    period text NOT NULL,
    period_date timestamp with time zone NOT NULL,
    service_id uuid DEFAULT '00000000-0000-0000-0000-000000000000'::uuid NOT NULL,
    route_id uuid DEFAULT '00000000-0000-0000-0000-000000000000'::uuid NOT NULL,
    value integer,
    ttl timestamp with time zone
);


ALTER TABLE public.ratelimiting_metrics OWNER TO kong;

--
-- Name: rbac_role_endpoints; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.rbac_role_endpoints (
    role_id uuid NOT NULL,
    workspace text NOT NULL,
    endpoint text NOT NULL,
    actions smallint NOT NULL,
    comment text,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    negative boolean NOT NULL,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.rbac_role_endpoints OWNER TO kong;

--
-- Name: rbac_role_entities; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.rbac_role_entities (
    role_id uuid NOT NULL,
    entity_id text NOT NULL,
    entity_type text NOT NULL,
    actions smallint NOT NULL,
    negative boolean NOT NULL,
    comment text,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.rbac_role_entities OWNER TO kong;

--
-- Name: rbac_roles; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.rbac_roles (
    id uuid NOT NULL,
    name text NOT NULL,
    comment text,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    is_default boolean DEFAULT false,
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.rbac_roles OWNER TO kong;

--
-- Name: rbac_user_groups; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.rbac_user_groups (
    user_id uuid NOT NULL,
    group_id uuid NOT NULL
);


ALTER TABLE public.rbac_user_groups OWNER TO kong;

--
-- Name: rbac_user_roles; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.rbac_user_roles (
    user_id uuid NOT NULL,
    role_id uuid NOT NULL,
    role_source text
);


ALTER TABLE public.rbac_user_roles OWNER TO kong;

--
-- Name: rbac_users; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.rbac_users (
    id uuid NOT NULL,
    name text NOT NULL,
    user_token text NOT NULL,
    user_token_ident text,
    comment text,
    enabled boolean NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    ws_id uuid DEFAULT '52490fa1-0cb5-4ac6-a0c7-d4ca986222f8'::uuid,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.rbac_users OWNER TO kong;

--
-- Name: response_ratelimiting_metrics; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.response_ratelimiting_metrics (
    identifier text NOT NULL,
    period text NOT NULL,
    period_date timestamp with time zone NOT NULL,
    service_id uuid DEFAULT '00000000-0000-0000-0000-000000000000'::uuid NOT NULL,
    route_id uuid DEFAULT '00000000-0000-0000-0000-000000000000'::uuid NOT NULL,
    value integer
);


ALTER TABLE public.response_ratelimiting_metrics OWNER TO kong;

--
-- Name: rl_counters; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.rl_counters (
    key text NOT NULL,
    namespace text NOT NULL,
    window_start integer NOT NULL,
    window_size integer NOT NULL,
    count integer
);


ALTER TABLE public.rl_counters OWNER TO kong;

--
-- Name: routes; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.routes (
    id uuid NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    name text,
    service_id uuid,
    protocols text[],
    methods text[],
    hosts text[],
    paths text[],
    snis text[],
    sources jsonb[],
    destinations jsonb[],
    regex_priority bigint,
    strip_path boolean,
    preserve_host boolean,
    tags text[],
    https_redirect_status_code integer,
    headers jsonb,
    path_handling text DEFAULT 'v0'::text,
    ws_id uuid,
    request_buffering boolean,
    response_buffering boolean,
    expression text,
    priority bigint
);


ALTER TABLE public.routes OWNER TO kong;

--
-- Name: schema_meta; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.schema_meta (
    key text NOT NULL,
    subsystem text NOT NULL,
    last_executed text,
    executed text[],
    pending text[]
);


ALTER TABLE public.schema_meta OWNER TO kong;

--
-- Name: services; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.services (
    id uuid NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    name text,
    retries bigint,
    protocol text,
    host text,
    port bigint,
    path text,
    connect_timeout bigint,
    write_timeout bigint,
    read_timeout bigint,
    tags text[],
    client_certificate_id uuid,
    tls_verify boolean,
    tls_verify_depth smallint,
    ca_certificates uuid[],
    ws_id uuid,
    enabled boolean DEFAULT true
);


ALTER TABLE public.services OWNER TO kong;

--
-- Name: session_metadatas; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.session_metadatas (
    id uuid NOT NULL,
    session_id uuid,
    sid text,
    subject text,
    audience text,
    created_at timestamp with time zone
);


ALTER TABLE public.session_metadatas OWNER TO kong;

--
-- Name: sessions; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.sessions (
    id uuid NOT NULL,
    session_id text,
    expires integer,
    data text,
    created_at timestamp with time zone,
    ttl timestamp with time zone
);


ALTER TABLE public.sessions OWNER TO kong;

--
-- Name: sm_vaults; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.sm_vaults (
    id uuid NOT NULL,
    ws_id uuid,
    prefix text,
    name text NOT NULL,
    description text,
    config jsonb NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    updated_at timestamp with time zone,
    tags text[]
);


ALTER TABLE public.sm_vaults OWNER TO kong;

--
-- Name: snis; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.snis (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    name text NOT NULL,
    certificate_id uuid,
    tags text[],
    ws_id uuid,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.snis OWNER TO kong;

--
-- Name: tags; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.tags (
    entity_id uuid NOT NULL,
    entity_name text,
    tags text[]
);


ALTER TABLE public.tags OWNER TO kong;

--
-- Name: targets; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.targets (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(3)),
    upstream_id uuid,
    target text NOT NULL,
    weight integer NOT NULL,
    tags text[],
    ws_id uuid,
    cache_key text,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(3))
);


ALTER TABLE public.targets OWNER TO kong;

--
-- Name: upstreams; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.upstreams (
    id uuid NOT NULL,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(3)),
    name text,
    hash_on text,
    hash_fallback text,
    hash_on_header text,
    hash_fallback_header text,
    hash_on_cookie text,
    hash_on_cookie_path text,
    slots integer NOT NULL,
    healthchecks jsonb,
    tags text[],
    algorithm text,
    host_header text,
    client_certificate_id uuid,
    ws_id uuid,
    hash_on_query_arg text,
    hash_fallback_query_arg text,
    hash_on_uri_capture text,
    hash_fallback_uri_capture text,
    use_srv_name boolean DEFAULT false,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.upstreams OWNER TO kong;

--
-- Name: vault_auth_vaults; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vault_auth_vaults (
    id uuid NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    name text,
    protocol text,
    host text,
    port bigint,
    mount text,
    vault_token text,
    kv text
);


ALTER TABLE public.vault_auth_vaults OWNER TO kong;

--
-- Name: vaults; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vaults (
    id uuid NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    name text,
    protocol text,
    host text,
    port bigint,
    mount text,
    vault_token text
);


ALTER TABLE public.vaults OWNER TO kong;

--
-- Name: vitals_code_classes_by_cluster; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_code_classes_by_cluster (
    code_class integer NOT NULL,
    at timestamp with time zone NOT NULL,
    duration integer NOT NULL,
    count integer
);


ALTER TABLE public.vitals_code_classes_by_cluster OWNER TO kong;

--
-- Name: vitals_code_classes_by_workspace; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_code_classes_by_workspace (
    workspace_id uuid NOT NULL,
    code_class integer NOT NULL,
    at timestamp with time zone NOT NULL,
    duration integer NOT NULL,
    count integer
);


ALTER TABLE public.vitals_code_classes_by_workspace OWNER TO kong;

--
-- Name: vitals_codes_by_consumer_route; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_codes_by_consumer_route (
    consumer_id uuid NOT NULL,
    service_id uuid,
    route_id uuid NOT NULL,
    code integer NOT NULL,
    at timestamp with time zone NOT NULL,
    duration integer NOT NULL,
    count integer
)
WITH (autovacuum_vacuum_scale_factor='0.01', autovacuum_analyze_scale_factor='0.01');


ALTER TABLE public.vitals_codes_by_consumer_route OWNER TO kong;

--
-- Name: vitals_codes_by_route; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_codes_by_route (
    service_id uuid,
    route_id uuid NOT NULL,
    code integer NOT NULL,
    at timestamp with time zone NOT NULL,
    duration integer NOT NULL,
    count integer
)
WITH (autovacuum_vacuum_scale_factor='0.01', autovacuum_analyze_scale_factor='0.01');


ALTER TABLE public.vitals_codes_by_route OWNER TO kong;

--
-- Name: vitals_locks; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_locks (
    key text NOT NULL,
    expiry timestamp with time zone
);


ALTER TABLE public.vitals_locks OWNER TO kong;

--
-- Name: vitals_node_meta; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_node_meta (
    node_id uuid NOT NULL,
    first_report timestamp without time zone,
    last_report timestamp without time zone,
    hostname text
);


ALTER TABLE public.vitals_node_meta OWNER TO kong;

--
-- Name: vitals_stats_days; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_stats_days (
    node_id uuid NOT NULL,
    at integer NOT NULL,
    l2_hit integer DEFAULT 0,
    l2_miss integer DEFAULT 0,
    plat_min integer,
    plat_max integer,
    ulat_min integer,
    ulat_max integer,
    requests integer DEFAULT 0,
    plat_count integer DEFAULT 0,
    plat_total integer DEFAULT 0,
    ulat_count integer DEFAULT 0,
    ulat_total integer DEFAULT 0
);


ALTER TABLE public.vitals_stats_days OWNER TO kong;

--
-- Name: vitals_stats_hours; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_stats_hours (
    at integer NOT NULL,
    l2_hit integer DEFAULT 0,
    l2_miss integer DEFAULT 0,
    plat_min integer,
    plat_max integer
);


ALTER TABLE public.vitals_stats_hours OWNER TO kong;

--
-- Name: vitals_stats_minutes; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_stats_minutes (
    node_id uuid NOT NULL,
    at integer NOT NULL,
    l2_hit integer DEFAULT 0,
    l2_miss integer DEFAULT 0,
    plat_min integer,
    plat_max integer,
    ulat_min integer,
    ulat_max integer,
    requests integer DEFAULT 0,
    plat_count integer DEFAULT 0,
    plat_total integer DEFAULT 0,
    ulat_count integer DEFAULT 0,
    ulat_total integer DEFAULT 0
);


ALTER TABLE public.vitals_stats_minutes OWNER TO kong;

--
-- Name: vitals_stats_seconds; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.vitals_stats_seconds (
    node_id uuid NOT NULL,
    at integer NOT NULL,
    l2_hit integer DEFAULT 0,
    l2_miss integer DEFAULT 0,
    plat_min integer,
    plat_max integer,
    ulat_min integer,
    ulat_max integer,
    requests integer DEFAULT 0,
    plat_count integer DEFAULT 0,
    plat_total integer DEFAULT 0,
    ulat_count integer DEFAULT 0,
    ulat_total integer DEFAULT 0
);


ALTER TABLE public.vitals_stats_seconds OWNER TO kong;

--
-- Name: workspace_entities; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.workspace_entities (
    workspace_id uuid NOT NULL,
    workspace_name text,
    entity_id text NOT NULL,
    entity_type text,
    unique_field_name text NOT NULL,
    unique_field_value text
);


ALTER TABLE public.workspace_entities OWNER TO kong;

--
-- Name: workspace_entity_counters; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.workspace_entity_counters (
    workspace_id uuid NOT NULL,
    entity_type text NOT NULL,
    count integer
);


ALTER TABLE public.workspace_entity_counters OWNER TO kong;

--
-- Name: workspaces; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.workspaces (
    id uuid NOT NULL,
    name text,
    comment text,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0)),
    meta jsonb,
    config jsonb,
    updated_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.workspaces OWNER TO kong;

--
-- Name: ws_migrations_backup; Type: TABLE; Schema: public; Owner: kong
--

CREATE TABLE public.ws_migrations_backup (
    entity_type text,
    entity_id text,
    unique_field_name text,
    unique_field_value text,
    created_at timestamp with time zone DEFAULT timezone('UTC'::text, CURRENT_TIMESTAMP(0))
);


ALTER TABLE public.ws_migrations_backup OWNER TO kong;

--
-- Name: clustering_rpc_requests id; Type: DEFAULT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.clustering_rpc_requests ALTER COLUMN id SET DEFAULT nextval('public.clustering_rpc_requests_id_seq'::regclass);


--
-- Name: clustering_sync_version version; Type: DEFAULT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.clustering_sync_version ALTER COLUMN version SET DEFAULT nextval('public.clustering_sync_version_version_seq'::regclass);


--
-- Data for Name: acls; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.acls (id, created_at, consumer_id, "group", cache_key, tags, ws_id) FROM stdin;
\.


--
-- Data for Name: acme_storage; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.acme_storage (id, key, value, created_at, ttl) FROM stdin;
\.


--
-- Data for Name: admins; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.admins (id, created_at, updated_at, consumer_id, rbac_user_id, rbac_token_enabled, email, status, username, custom_id, username_lower) FROM stdin;
8e093d19-b0e6-4ef4-92b1-5b6b5ce12c16	2025-07-04 04:11:10	2025-07-04 04:11:10	7819c050-221b-47e1-b517-1df5963a1e55	b0ca086d-ca49-4983-bd1c-ade4600f9cb4	t	\N	\N	kong_admin	\N	kong_admin
\.


--
-- Data for Name: application_instances; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.application_instances (id, created_at, updated_at, status, service_id, application_id, composite_id, suspended, ws_id) FROM stdin;
\.


--
-- Data for Name: applications; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.applications (id, created_at, updated_at, name, description, redirect_uri, meta, developer_id, consumer_id, custom_id, ws_id) FROM stdin;
\.


--
-- Data for Name: audit_objects; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.audit_objects (id, request_id, entity_key, dao_name, operation, entity, rbac_user_id, signature, ttl, removed_from_entity, request_timestamp) FROM stdin;
\.


--
-- Data for Name: audit_requests; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.audit_requests (request_id, request_timestamp, client_ip, path, method, payload, status, rbac_user_id, workspace, signature, ttl, removed_from_payload, rbac_user_name, request_source) FROM stdin;
\.


--
-- Data for Name: basicauth_credentials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.basicauth_credentials (id, created_at, consumer_id, username, password, tags, ws_id) FROM stdin;
091eec81-6944-4d37-8484-afa74f191a35	2025-07-04 04:11:10+00	7819c050-221b-47e1-b517-1df5963a1e55	kong_admin	f1c5ecf19ea6f8413e56e025f8e4aacf675b5034	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8
\.


--
-- Data for Name: ca_certificates; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.ca_certificates (id, created_at, cert, tags, cert_digest, updated_at) FROM stdin;
\.


--
-- Data for Name: certificates; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.certificates (id, created_at, cert, key, tags, ws_id, cert_alt, key_alt, updated_at) FROM stdin;
\.


--
-- Data for Name: cluster_events; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.cluster_events (id, node_id, at, nbf, expire_at, channel, data) FROM stdin;
\.


--
-- Data for Name: clustering_data_planes; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.clustering_data_planes (id, hostname, ip, last_seen, config_hash, ttl, version, sync_status, updated_at, labels, cert_details, rpc_capabilities) FROM stdin;
\.


--
-- Data for Name: clustering_rpc_requests; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.clustering_rpc_requests (id, node_id, reply_to, ttl, payload) FROM stdin;
\.


--
-- Data for Name: clustering_sync_delta; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.clustering_sync_delta (version, type, pk, ws_id, entity) FROM stdin;
\.


--
-- Data for Name: clustering_sync_lock; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.clustering_sync_lock (id) FROM stdin;
1
\.


--
-- Data for Name: clustering_sync_version; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.clustering_sync_version (version) FROM stdin;
\.


--
-- Data for Name: consumer_group_consumers; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.consumer_group_consumers (created_at, consumer_group_id, consumer_id, cache_key, updated_at) FROM stdin;
\.


--
-- Data for Name: consumer_group_plugins; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.consumer_group_plugins (id, created_at, consumer_group_id, name, cache_key, config, ws_id, updated_at) FROM stdin;
\.


--
-- Data for Name: consumer_groups; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.consumer_groups (id, created_at, name, ws_id, tags, updated_at) FROM stdin;
\.


--
-- Data for Name: consumer_reset_secrets; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.consumer_reset_secrets (id, consumer_id, secret, status, client_addr, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: consumers; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.consumers (id, created_at, username, custom_id, tags, ws_id, username_lower, updated_at, type) FROM stdin;
7819c050-221b-47e1-b517-1df5963a1e55	2025-07-04 04:11:10+00	kong_admin_ADMIN_	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	_admin_	2025-07-04 04:11:10+00	2
cea7ebd2-231b-41f1-8237-589f10df04cf	2025-07-04 04:50:41+00	token_server	\N	{}	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	token_server	2025-07-04 04:50:41+00	0
\.


--
-- Data for Name: credentials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.credentials (id, consumer_id, consumer_type, plugin, credential_data, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: custom_plugins; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.custom_plugins (id, ws_id, name, schema, handler, created_at, updated_at, tags) FROM stdin;
\.


--
-- Data for Name: degraphql_routes; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.degraphql_routes (id, service_id, methods, uri, query, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: developers; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.developers (id, created_at, updated_at, email, status, meta, custom_id, consumer_id, rbac_user_id, ws_id) FROM stdin;
\.


--
-- Data for Name: document_objects; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.document_objects (id, created_at, updated_at, service_id, path, ws_id) FROM stdin;
\.


--
-- Data for Name: event_hooks; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.event_hooks (id, created_at, source, event, handler, on_change, snooze, config, updated_at) FROM stdin;
\.


--
-- Data for Name: files; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.files (id, path, checksum, contents, created_at, ws_id, updated_at) FROM stdin;
\.


--
-- Data for Name: filter_chains; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.filter_chains (id, name, enabled, route_id, service_id, ws_id, cache_key, filters, tags, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: graphql_ratelimiting_advanced_cost_decoration; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.graphql_ratelimiting_advanced_cost_decoration (id, service_id, type_path, add_arguments, add_constant, mul_arguments, mul_constant, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: group_rbac_roles; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.group_rbac_roles (created_at, group_id, rbac_role_id, workspace_id, updated_at) FROM stdin;
\.


--
-- Data for Name: groups; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.groups (id, created_at, name, comment, updated_at) FROM stdin;
\.


--
-- Data for Name: header_cert_auth_credentials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.header_cert_auth_credentials (id, created_at, consumer_id, subject_name, ca_certificate_id, cache_key, tags, ws_id) FROM stdin;
\.


--
-- Data for Name: hmacauth_credentials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.hmacauth_credentials (id, created_at, consumer_id, username, secret, tags, ws_id) FROM stdin;
\.


--
-- Data for Name: jwt_secrets; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.jwt_secrets (id, created_at, consumer_id, key, secret, algorithm, rsa_public_key, tags, ws_id) FROM stdin;
eda76d6f-f017-40e6-9208-dd73bc4547c5	2025-07-04 04:51:14+00	cea7ebd2-231b-41f1-8237-589f10df04cf	O4o1FtbjggEYqFRNA6EwF8qn48qHFlwx	0B9I603NqluvINON5g77tnhwbBfXqWCq	HS256	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8
\.


--
-- Data for Name: jwt_signer_jwks; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.jwt_signer_jwks (id, name, keys, previous, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: key_sets; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.key_sets (id, name, tags, ws_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: keyauth_credentials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.keyauth_credentials (id, created_at, consumer_id, key, tags, ttl, ws_id) FROM stdin;
\.


--
-- Data for Name: keyauth_enc_credentials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.keyauth_enc_credentials (id, created_at, consumer_id, key, key_ident, ws_id, tags, ttl) FROM stdin;
\.


--
-- Data for Name: keyring_keys; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.keyring_keys (id, recovery_key_id, key_encrypted, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: keyring_meta; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.keyring_meta (id, state, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: keys; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.keys (id, set_id, name, cache_key, ws_id, kid, jwk, pem, tags, created_at, updated_at, x5t) FROM stdin;
\.


--
-- Data for Name: konnect_applications; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.konnect_applications (id, ws_id, created_at, client_id, scopes, tags, consumer_groups, auth_strategy_id, application_context, exhausted_scopes) FROM stdin;
\.


--
-- Data for Name: legacy_files; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.legacy_files (id, auth, name, type, contents, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: license_data; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.license_data (node_id, req_cnt, license_creation_date, year, month) FROM stdin;
3b1feb53-68a5-4f49-88b6-c040b9048f03	11092	2017-07-20 00:00:00	2025	7
2f1fca55-6ca3-488c-9c13-78bf65fb2486	3	2017-07-20 00:00:00	2025	7
\.


--
-- Data for Name: license_llm_data; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.license_llm_data (id, model_name, license_creation_date, year, week_of_year) FROM stdin;
\.


--
-- Data for Name: licenses; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.licenses (id, payload, created_at, updated_at, checksum) FROM stdin;
\.


--
-- Data for Name: locks; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.locks (key, owner, ttl) FROM stdin;
\.


--
-- Data for Name: login_attempts; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.login_attempts (consumer_id, attempts, ttl, created_at, updated_at, attempt_type) FROM stdin;
\.


--
-- Data for Name: mtls_auth_credentials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.mtls_auth_credentials (id, created_at, consumer_id, subject_name, ca_certificate_id, cache_key, ws_id, tags) FROM stdin;
\.


--
-- Data for Name: oauth2_authorization_codes; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.oauth2_authorization_codes (id, created_at, credential_id, service_id, code, authenticated_userid, scope, ttl, challenge, challenge_method, ws_id, plugin_id) FROM stdin;
\.


--
-- Data for Name: oauth2_credentials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.oauth2_credentials (id, created_at, name, consumer_id, client_id, client_secret, redirect_uris, tags, client_type, hash_secret, ws_id) FROM stdin;
\.


--
-- Data for Name: oauth2_tokens; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.oauth2_tokens (id, created_at, credential_id, service_id, access_token, refresh_token, token_type, expires_in, authenticated_userid, scope, ttl, ws_id) FROM stdin;
\.


--
-- Data for Name: oic_issuers; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.oic_issuers (id, issuer, configuration, keys, secret, created_at) FROM stdin;
\.


--
-- Data for Name: oic_jwks; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.oic_jwks (id, jwks) FROM stdin;
c3cfba2d-1617-453f-a416-52e6edb5f9a0	{"keys": [{"k": "GJ61HGmC_NXUElG___9moeKyFpZWuOtpRoBgtXrEk7E", "alg": "HS256", "kid": "FznlewFCAL1W7hDTkurY1YeIx3sCnHBJO8W8X4UeFNY", "kty": "oct", "use": "sig"}, {"k": "L3Q68pI3d3ilwDsIEH8A9x1xFTTFWhZPUm6CUPfp8LUUQsYRxkWYcqRXVr26t90I", "alg": "HS384", "kid": "Z0zVhwHYTtRGrxV5vFbtfA4usyLRUaOGc7W2WHbeD64", "kty": "oct", "use": "sig"}, {"k": "hd7FVWChHMqUvNwDooZGS325Uv6YP8OI9LQ32ipnAGQlx1Ply0MDx0CAfD1rN42GF4r30mz3etdpNteoDWTpnQ", "alg": "HS512", "kid": "MAZePe6bRUcYk9NA6dDA2SMhwXI1G5R4mSJhTU-i3NA", "kty": "oct", "use": "sig"}, {"d": "HQ8BjsTbIaG4xrWDNwWM2Dv4WUwgcv2hOQFeMZ4UY5zcePle0DVE-LF1bT6ZTOykswPbRn-2pEAzo31hgel-ft6M7Vq7Ae8cokMF2EWhrQJ6sdVM0_SYyJEVoIgEMnWJIdTyvDivljz5ao1vDiWbyqz_TdcWJB_R3Tfq9yPGzpao-b8N2vImbdrBVfl2wOjFTY0lz4MZPRcJWg698EqbhrTeXodpfaYZPhNosM64044QnQ4jVE9MbWGRNc6DKUTRnOHnRocmFNjaUVdOVGQUaQrLEPhs6_Ovotp1_bDQdqRtj2jnW2jcGEJxSVnHVOZkOpwwImtnWLkd26rMxZ2pWQ", "e": "AQAB", "n": "yaHNXPOqM3eRbRpDEguK9TUc9GKZOUYEIxbF41TBWoj8xSb_T5uHfkpgnmXtSP2F95zcQlLqc3y0LlJerudtbgtut-ugnGUdSGLhG-4p-Y2qhYP9G1Unk6IrksKTBsvCSyKgptabR0W63wmAwtf3HhR9g_s6qM3wJSCAxicHWu1psJ5pa9XR_L_UxTSaHPyAF3C5r2r2gh9XQIMzTRh-KbqmAwLnwcJWN6SHvjmWmIAMWNq0HzAQJMGJqYh_WhrAxyOSYrIWsSchLlcpU2LcUC2XLjNGjXnbxZcuXTTtbLjO42gsFjCt1STuDLvUVPVmVeIvqEoeknxN0p0HJkXQ_w", "p": "9PC-8jusoWhwac7J1gkCLOnmtDTEJ7TmQSCTlSQgQa3d1XElq7sAd3D36fMefW26r5znVQsSXOTVt0JHJpbU8uNrvLiTfdrNzEA8FLPGzkbBev1WguwpkAM3QKGNRLgkFJ5JMipEYTWxx-qBoY7luK9a7RKeOro3Uc3OxlxD3ds", "q": "0rx0-881bkTBD5nxUaklR4qaYfDo-eRxpB8eznV_VDf7k3dH46ouILBgfJyjTqnWu0VLeh3UVgUGEeYHj66Xt3SMpzkjKT_CYi3IsURyX7Lfb5jgEuZi0b-NoXUblHI0nD4rc1cdWCqB4P8LUQfgwd7JGnQu3UFJOVyUISyZ7K0", "dp": "7MoADjTaQn-wjNk4Dg5PaBQGz5djOe-L2NIhWkC-XkAzADCN9mKlpNI1GJnLk_4MR9ErYe9-JQekA7GzDv8phlWMxbi5_SUVZquCaZESVD_as4dNpuufknVJaNXEt_Y8CyKqKDDsQf2ijF2MTB7UvaHoCN_Cz4tOB2sqxmBTy5k", "dq": "kxM6JgAgjzvm7eeHt3FwdjSyuIY1YypfcabSjk8MtY94whuujaLZHLFcNbvwcdcw6XRcT_b_maVZ7iX3wvycIEp0W8UR2BkKphDED4vwsv-Y1z7_q9EkyLSCuVuNCxaFx0HQe2giL8VC7e-ySzRPVZ8SBaduibalzyXgsCJ7ruU", "qi": "V3ghL6ACuXLyLtmDHZRwzXZKYEqBdueX6QqkZK2ObBIdozeoq3hy1m3jccGy5aXTS0i_WYlbFBgTok9jkv4FE9bZkCoBV0aOY2DHo7hTG5pqMBRIH-UJljyeQ1IiO5nTzso23XiNfiKPlqU4bQ0bz_o-PL0YhsRipk7k1PmjF0U", "alg": "RS256", "kid": "T3l5ojjlOQdYU6yeEwHAUjEsjTlaXHP0J8Zct4bkOnM", "kty": "RSA", "use": "sig"}, {"d": "Agy9r5WAt3_Y2fAofdBc61nhE3A0TPBVEsxvxNz8-A050ytM0H8XROwI5uglKtaXbDULLvq1sPkgge6jQC91lxGKT-_hlJgkZaWG2CUMDQC-mAw06yPY2TimnhLT_t1FRmBtQodPzJ_EOAuOt-whk-faqtUERuH6pd2bb-Bg7Rjme_MwCz50jc7SIEfJJpO2o2c58L6zg_peMNYD3dF1-dG46KjGlkyXg6GS7vTaJfr7bKZ-1QpBNQS5O035NUF0wk_Onjhl5tbdW8cMX20RtJmcFr9pgL-bXqMeSgsldFGb2W8ocrQoM08kbgPaxgHwaOtX0INZYEXWn0SuyTPZfQ", "e": "AQAB", "n": "rTmEUPBrSU4vy4QFRcUnBgpf1isofFSDGMLzuEk6m4vCuuIeog11BzKdHOZw5FsyRneg75_UUiMmejBBoic1fKjPy-gSpKIUuiga9eVTXoJCnRD9TvvernZ0xI_zWrP0qOrrBfcx_Yu_5YR2Ae5W1LuxAA-NFrJvMhiHLKtDARrFNtUMlZnknOFNFXTeSa1GvLb10OM-XpmYG3fhLWXfCouut2m4DE6xvPWrGaAKBM8f_cD0tCIYbQCziZctiLsgeTppFPWK7HniuRQcwHtam0PbKUhY27giGXJkswebBrQN83wc_nioEF0qlAclN54e_p0mO6QD8YoPMPl5Iy34Iw", "p": "6xLJTvZr9OVAmarlK1bIQLTkjm9dbKIrypbVt8ZXQIh2S7J4hcXckT5EJlPwvEdqJ1XQ_ocx2aalazYoe9nRTYP3Qmj3qQlN-lCEHOSiO5Re47CgBBWhVeHKJ895a7la42IhVuY2nyygtaSSjWlxZU9hCtEnCseDTTh4jPgBr_0", "q": "vKU6H4WYoOY27ClkzrkrmI-KqBeIC0fwTjGK_LpNPjBQ5XOiDcX5XlEb9Sob4AidyBYOLCMBqNCc5EscqF1SdsThIbYOBHh1SrvyKE5AEm1KFTpueq7x85W-DKOzUqKpKZAyX85xmiYj3gX5XeqotXCYdenbQZU9wXC6V_y_cp8", "dp": "yNVBBeREMXMXcPHvXsqCxb2GMyZ0A9a9YRTivGKIssqjfmKY03lKyxAOe0xltQWybK7Fol9wuInGc8VOaxmZ1wby3Vr-k_0uELhvVpwC-rH1K8P_wd2U--Guq7p2-xDd1LxGzY5bhjxDhUx4JLa8OZjKyMoN-AiiZfvkClPsZSU", "dq": "eFHKKKDFIJq1HRupuFBShhPHlD6t_sP7eWQQCfGpoqiecDq4eaBz1LxW4J_i95mHST8H-w642Dt2VgWyWZBsMGSSUzYS6rcJ2IjXgu2l4BrkzXVfUdCTcZFojhc7INGuo1vdRU4fFvqMleepjUVGJbOTCmIF_mZ55Q9JSyVh1nE", "qi": "G2LX1AZq0-49sqtELg2acmfZRTYNI4KwJJcSsN8PGbZP99HynvJY3hofdpmPa_BPMSe0UUehIXoKvAnjGs-nD5W3EjAKMZDiQazQUjl_rSw5Q7_tWsVPKLTMva-FU4Jgur8AUpuIDZPuL_1HhsrYHAdgBnPgtU2h_JmDZe4bO7w", "alg": "RS384", "kid": "M2I4zyLKlw75AHaywrq_VjB4kh2bdoIScFV1Zi12Rs8", "kty": "RSA", "use": "sig"}, {"d": "RBeFVMKRvUsE5QXQVRwRSlPjSk3ip7io9g071Wcv00PuOwFl2PW0VA_pVH652vHXIa0AlhHr-86BZtmRJCoVcgAZymWr2UtdAAEwbKg8Qn7HOGmKBNaUgJ0s3yKBUxoCtRoUGOz8EvLiED4hagNetp_FVupUFL4ycDAkC-3SES3IbCffhuncURrq93LRkvjzDWPrsAOzijuJAqiLgJMLEseRzlKNsIIDFMuWekTCA6-N2IOVdvRnsMct9XGusz9n1gLFIbpFEAkRvKl9Hd_-ZWgHb9uGWGHC1YVCT73jWKz6Pw3hYdCoMDjTI9Dztrx9BkAlOWGiwbCTh8UXusex9Q", "e": "AQAB", "n": "5k6CtXa_lWqkZV7zrc0MDD_wwYsMQxS0luHbB0KcV5dvPJCg5iUhEqbYRUip0jeBDcnSUHQvnK32rVbmqaS9ietfnUYSjI0ARpSSwWrc-nVHMdoTCxYJChwD_nxREBplEobemARYwawhkrNfh8hBZhMkvqSetxtsdG5E-usUNSTDtJ9R6WG8YU8_uooreCyiQ2U43Yrn9KXBO2uwpk48C3xJWCkH4IrfnHuUlGRoGzakvXekZ446FPaMjB7bz0g99397L_eekuXF0LyX04ws5K0Uuy4Hpxgni6miguPqY1XvmnGOGaZYoGj9CuYovI3liHKxZ9DzgI7NjXBPUQkeew", "p": "8x_OxczPNsQF1ujqGbZZnVN60IvC-SUUSnUb2IEgME4ooWIjDIllK0ilh3Oio7_hrlLekHTH-8BNYPWh0LrepkW2h0BRDs5-7BJfHlC-_mBKs_RWzWCBNj85kOmiLtUl3xHojFKde4q98Ujt6anuviULKvzaj1ZsBqHsuD57rh0", "q": "8oDtRi1mL7lzje0aKqoOSZaVeVFBBN5xnQ9khoA3OeXJSXU3S4QSVakJjd9-Y6jl3ruzNGces6L2dpcOAItPt0qyp-vuyzCw8h-CVQWDA2NlLKaXTk5iGvwl33_LeoGBtVuUgIr6wNbYcZsxDpvsadqnD7O98RJUixQsBZQMu3c", "dp": "0s0pg-fpYxrj1Utub4zkdr0DJ_-Gtm-oAEc6NacAOeIK6KMmsKAQ0E7U93UT99dP3s6Lm8UmAOrkLMXN4CWnF5Qqh2sE3Um-UwI65xYsBHOJUi7xdHHQlzCKwVNSBDlb9OaaM9EOKyhJOQR7BLcLAmMsI8HJ366XS8jqb3X91yE", "dq": "o9f1-B8-VmpPJ4oVztOc2Z1TGmeKrXZ5fqtiuPkx1v1o9SesF2qBVLzjxqZE56K6xLU-y94MQUSOjutaO4XkcyuV75yZd1FLjBs73KPfs89ZEUTxH6gX4rGQCSR33ypW76iTehHsf5PpI0C-cWdp4M8pZpODdrhyxJD5eT9hJts", "qi": "Fuv2lpt0e0n-LSRWdO_vI2mZkr3FryYqgUtXA8SUEwX6-POrseQVOEi2nv4X3K98q7KVYHtZtly8-P7iOnpfWvO56xk9zOHKNEzi0DqwzF-tRL_cf9wFB18sm4bIum-eoQldDlZeou0U39ItyGJmlyv2aNXd0yVMmXV9pugX2dc", "alg": "RS512", "kid": "HdcXk5wB6fh7BXj2GmTcNjDudtm_iUfzXskXZsTnxQw", "kty": "RSA", "use": "sig"}, {"d": "YHVUSiIgJSsObRbltiTXLjCdbtLkPkuxi0O9PDDZZzXsosLO7S5X61hBqxGiIQcZB_T5BbWUmH8PSmP2c4Eu_r4zpxqUMwQjVe3-Y5j1eZ49VkLzM8Nd41l0SQAk7XuLNp8N_eCyeFFy3xAswxIbjHGJJv0uKrxgufaDB1h1k6iyoRHosUXgR4HhyJg1MLLU-qd7XnOx-YqjnXoQKWjv5JNUO6vQTQTg6o93I48w10K90vpaCXRupwmqGwriSZa0hi_lYPq5GAx5N2wyLc0bhdnMm89VOW3P54I9poSMi1IyxFYg-qPJxoysXpc4FB62rr0HNcArqZUTJWGrMVEJkw", "e": "AQAB", "n": "7UrcHjdOEKU9Zp4N0Znzc0v5LAJujHXeIs7d8sTEtKGv0N65btydPuZ0FcSsXG9udzlm14Q22fK6MlYtA9rMDjbYRdMH4XH8UfswUSyX33qcb8yB7SRkcdO05aZ338Faa-gnwOk-T_9qZ8NX9lddgoVYnk8WTEGM5FT54dmtRgnz0VHpjDgLEg1Scz-pOaVoIzkUfz5Z5Q2nI2UUpVPMAoWC6iV1NEFWTgoFQ04jq6rPKAQ8UUll07ri-2944vtiiLULUrsFdgUQlSPoF6TJRNDrRJX4JhrxzKINp9SmDcHOllST-YI7whTJuRq1X53y3u6o6Kw5WyPjYNiEnGgDUQ", "p": "-lUamUoLidc8hb2E__tXIQdPg9-TDORTi9Ntpq0sPMNncBF-q-Og_QseiPenyCfL335xfuRQH7ouIVbsxz0LSzpxjvpwekwFY4MI1WqMOx_B1AyzXivNRPBZ_YW1EN1sSbktNYhMtTFIYrDoln-fNgNeFI7C3-wrbEi7cjbkNy8", "q": "8qotd1Qkj-ZQRU72EFlBVQiBqaqgye2OGUsUEu4MnJ9tBFTM0h5pGsz6y9Zane5gTCkgiB_ArZWDYsa-hOIHzlekp9fZv1c0pT6uEAdec30VQWrEhNHwTBXsGWihcan7d9twg0qzl7IsKAonvt-fTvILQ3m_q1ZG4D7sSy8AzX8", "dp": "HJ_6qKik5SAtlYV0XekZr3csioHYSNMbEpQcp8CJnHEjtNbrSzB-SjEpTrQzBVo3n9jPlZj56Ibbm4hbDvVWA0YtkhHlJrbmrthe_DhwzJn3Cg5hvFT55fYt9CWD7OfE6DE6kDKDwvxdsJtWHU9-nyiHa4ZflkdlN76hYJASTJU", "dq": "ZUqbx9-bk2VzxDp9g71-A9yVIxlSHHfIhM5OBsjPAuGZQ-GRzXNcNA-z8Dn9gUbrc18HcUqvxsVOHoEPLbCbyYnT9SuofLIOC5vqVE5_In3nzXKfrUFkqJ3N-50tJqDpktJKWMNpy7xXqi7zAD2f5fgqs3OyifZth1U8lPV9xEk", "qi": "qV6E8Wd6abdWcdgSKrOb0j-7Osu9uKcAP-iX9xEIWl2v6_CH8GmN0vzEvAuoKeOhVS8j0WvGvDNgURbq2opMobXma_gbLmYbQFpdcFi8GRgz0UsL-LF7c2ZczVfslnRLMNNeWYbGprKXZ1APsC0uwLLyLX30nSohh59tfGNor8I", "alg": "PS256", "kid": "_9NWmIT_8cHwQHaAh3DE1SyQjecnmmOL36MJPEoN5pk", "kty": "RSA", "use": "sig"}, {"d": "Wnd9XQdJ9jDYJZM7_5WR0BvqsuaqEQcLXUH5HG602vrPCHC18TqkFDEn3RyOyf7sJMGn2r29zvwiX3zDoafJIcVugt8PTCWDAzaaEPSsQ2I5o1n0luXtdAQ5aXKjbPz-Y-72D_VI_lAUUB2d6h5gImChtM0Z5XIakkfwz4_1tdKV7NlM6beDcZqsOknCD1pGtwc75uHqyHzMb4fnciPtUwuFM9GrZO7Fbrfhgi6is18QwxNFrb3LzHZcVrI9yCqIHhNcoftZsPKcaCYody8Ae8bqaIPrCP-r4uAqQmyXkvn2-PLyXas-oeqblpBz0afz7C93v8IvctyskI5TgW38BQ", "e": "AQAB", "n": "x4lGgHBFHCZcD7oOBk-_4T1sYOcl4m6i7rUCMjgFbNV15NUwIW8AfD9tufeiJXMXxaLYOrzeGXnS0zi2mlf7FdUfXiS2a44_20blXqCYzpbgdXqfBpUdCdcu08xL7056g2zy3WPIAyU0C1ZpGX3l10NsFRBCZYj1Tpmvo016bij5vRsZQuka6AmJsQC6-3OoFYNF2nacL99qYSynbbXygOGEtXaLGAG09RKMV0I0kQnzNwhJ2og5q-4bmD3HjwkMOr-OhyfcdVfQHvm8VYiMErYZ6rH7Hh6lSymhLp5_7_MOt5MEoftJpiS8hH9je2a1-Xgcr3ioSxLtwMCzW-98Sw", "p": "7femjMh0SuzkQHI3_oFHEF719PgfdjMIjjLpT4wwMET7oaEijKT-yRfCx1xPEXDQ1LmsPx0Ax8bc9Q1LjZ8FSz7Z0pXT7rWvdcUI921s-HbMlJCHH8q5EeQx4emC08smfzm1Py43Lp_V-P8oHf-vbvrAus_BYQUsQ-kxBWBCXZU", "q": "1qgYeuTLjZM_vMxVnrsbbx42EPWoft38cA-AnQilp25ZGfq-wbvyCTEvqm0pUC1GAt8CM1HcKFY25s3QyhCLZUYvibpaDYvbo3ytl52ieETwPmlvjqSqg70Wx4gUGofbUuT91xd4Z_yMZT9STAI3Wh7WFsPtRUqCYxATiEzvOl8", "dp": "efO6pNdt0aAJcYWXcJRsCNXBkqM97GNG9SjeLGgZogMYOcLMY-uCryfAKE8ELln0i7ADt-xzI-6j-emwWImwOtmg46zpCaKOovoxGCw59jNbsJ9IwGAx7BtX_QCgjU3FEtpOKmzS1tAO5iZNNuJ705IqBxLFdaUjBEKTXr_wAuk", "dq": "YyKQ47jk1UarCguogz1QhzoBqEV8AtOMsUhHkr6amr_YwKnQDXM6bcpchC4UBWZTTiImRwNIVQy0IFXvVlLbvfLzI31_93-e-VssZLg1f6v-9CQHlG4yHdNnQNF0z2fs_9Sn8v1w6z10rZHX9SiU3bceErd7ziOCIjQbgThex6s", "qi": "CkQosQN6UPku4W8qQqusAtPQj0qfVcWa5KzjczsTr-FpazNKN5Tmqd1YZSTEBtRUf4W2_Ncs4tsUZUZsvqncQLNh-_47RCJULFoY_buXk5m4Ga7tCdbB5wIzjgeDYBIOy7gKFHNdndVXrFRkcmL8PFgx5mliNm2EKxRAL1sUp8A", "alg": "PS384", "kid": "ZVSB3_g3U-XHMK-CxdPnIubv8voDnGoXwVSNfpJfcbw", "kty": "RSA", "use": "sig"}, {"d": "IYaHBRKRNskAz-QEpROWTBn-18TqCXw4U5M8s7RU5sIBnm7U_YqG9rC6HV8n_dyQQaTuw86u_56ZwjI8NANG24c_3Esu4XJPWND5Zx0dC6Ej9Tx4KLkv3_SkUK1ls0OjEYHnmO96O9JeVzQJ1QIMUvSoH1i0WKv7hUkjINMtua8vr2fj-knRE-J6cx8xZORj93jccmsL6qVq0mAOeLujqG0FYVUrFkUvV-m9msx7KqV-lA4Sry8P5hbeQ9s_l1E2N9O2WPdgiUt6K76d3W-SgaOIXj8gmk_-pgsneEldIN5HzGPBv92iuh1mxkekRiyxcT22dyB8Hlfb7678ec25IQ", "e": "AQAB", "n": "wHJHQBjlR7C0TG9ocnNfiUGElIXE_g4TxbDxmnLF4eJdmiqLnLOSg8swrpEa_1E9dx0XZ0n9lcwOGW1dTnIEO8gE7DliNAClR9O-FEgdb2wuJ4S01-p81JQs3_8RFBkqbU9g88JGKtdC944dqpEa0VDEsShr1tbb0NZezWYaV_gVJOVH97GCz42L-z_nOKmcr4zheOeOdyy2KSQkUqoexx0SCSU8OSO-q-GWh-mBFushv9WnAJ0C5ipgyjrabLLiBxGWtW2ZGDiKOMGLElLtpI9MWp8erPf_f5NvjGmFc2KodSqfpIBvnys2wzdlRjQZeJ1Tutb45O8Y3FmafQSzVQ", "p": "7meWc0s-5rue3IGe5ucyeH6TioVZaLiwFyhfzyNNtVVb5RvoF7UJEH9DGcuhLDu2re6vjGx6cqMIHtKyYSpmRzQd0j-4y6sqJuzan2O2-sVZjapM0dTaAKz8ZERg6esfjuGzmKdaML8sjibd-hqMUzr7PIfvbGQ2mFhCi3u6P_k", "q": "zqZbSgewtl0sxDPhQM3Tl-VBodwAF5Rvzqfokj8qn-4YfmzhBVDzsinQem-vRl5LrWLcAKy3Sp0qi8-hsG22yWUmWWBMexMkD2h-6RRxez33qAB-dC-qF-PtXCZyuZLQZJCgks2JkJx_4hZXhdAU78qfpzN3VxLIXvtbsOEWXT0", "dp": "gaE7slcgza8I81dbwqvFyrTwnqphckqyHPVsGyJUF5xhSlOBQJrEYf8Ayl7ptTaG305gj9dQQVMakD_6lFDMqjzRVkglwEDCu8WgHnjGvtZeGmSWPqYhH6rv1clEdQzO7Q8Wa5Pbu5PHs0e4_UxALuVsRH6ZlfNrqCydimBtZmk", "dq": "wkYLFLHeI9O8vke3wGDAR9CzvyNAwuo9op6gOr0_qVMBm_loIsNUF27QHArbUOBdbQe_pLK7Ll5LHV15uI-sg_Gf9-5ceGgqQoIOrbkWZrJ_HWY5e6lD02noP_W1bVjkqWZ-TwUECvvWDgrLBXX1-OkY5uuviiCSVxkfvk4d5FE", "qi": "GlcaJe0NQ0c41LpN1oL6dqGwmjTtGQ8FEgReTRIgKo16dGX4lXvHyzKU4dgt9qU0dBxNUuziD4kv_xX1gzIUMpmsQk4rrPx9k-1Jzw2TRSo7V5oUJ02IB5xFeC_2Z0b4NaBJN7T471d115-llV0lSN7s9o8EiCR-PIUFD8xxdXM", "alg": "PS512", "kid": "SgPVy-vCP64uMA66yBusHXdwIX50tB9TzwGPYShW9Hg", "kty": "RSA", "use": "sig"}, {"d": "9FTMIlqJX7v3ZWSNVYKFrY7leo6-jY18x3TnUeoKmNg", "x": "c4FO6CPk8f0gMxm80iQQNvm3YCmjEM_SdeCFyVCKqAM", "y": "O2IPQ2A4mV45VstFKOZo7PYkpsAxza5YpX313E1zgE0", "alg": "ES256", "crv": "P-256", "kid": "_Az_sVKaWiHTIEGj1TCBlWbLMncLB4KuTwBFBQLt0j0", "kty": "EC", "use": "sig"}, {"d": "_KKg3fQcXXPUpbe3F213TuQvaKQnrgh89Vjj35FnqkRU5eyeGDo6sMIwccJvXkcu", "x": "5Lob4FEEVZSlF_u92njIjr3PbhNGgEbupzEzO5w_H_epcGyTdgDHkGk_0Aso2m7u", "y": "NvMgohDfQgKYOeiJOwZWDu3roRZ7Z7QlLN_9MxPMNiho-E1gYVM8WcY-XWHKhvXU", "alg": "ES384", "crv": "P-384", "kid": "e5K8aLzoSvROu-91xSfejUmSleMUj2Obb3TyA35wMpE", "kty": "EC", "use": "sig"}, {"d": "psHEFPQN6Z3wdvSlZkNGNtCt0pP18q9_eWnsjAEC3ar6wOKYpktyadTnLwnPoLFCqUHEQ5EOdb3E8ixH_iH3R8M", "x": "AUNsvmED5U80Qvg-RvjCwwDNMVzJ0qyzsSxwXseTfQzHcDHASp74Oa0i5B0A9LSubGR4iQK5-En1oNaniyqwnR1l", "y": "tLwXQPYDSz0JNuqsKI0CYXfCyiCbeyWxoHxL6Q8KQZvvevJRjXeyCzONwDXXOUGAv4e8pFG0QMoWKRwAOUAJOSA", "alg": "ES512", "crv": "P-521", "kid": "Pdjkva-FQtZjLRJnvdMzizgxBpYIC2NKWnCGmA34vhY", "kty": "EC", "use": "sig"}, {"d": "AjLjsr4gVtoM7NMd_YzuNIuCuHIxmPuRmiOPWA5stMg", "x": "hoy7NWl3W2ZGQPStbSU1eKhdiXGaOHsMYCIvi4XMjz4", "alg": "EdDSA", "crv": "Ed25519", "kid": "h_OhbivITA29Ko8GudkNzPVzp3FMg6tDb1XYZvq__CQ", "kty": "OKP", "use": "sig"}, {"d": "EE4UdNdp-Cj7BMD-lVN754DH4QvL2s7AAfC4ITs2X9052GTaGhA5AM7cxCxRNIPpkQvTDgZ3oXDD", "x": "l1HEOO4SSM-OLUbbKznSI5VB2a8zMZVaS4ACVJNWcNKejo5yBydhT9NGlohytjOndlANGwMcN1OA", "alg": "EdDSA", "crv": "Ed448", "kid": "rmRCYpqfleuatZa-3dLXrudWdB-ldVRE-l5Y8e0agYw", "kty": "OKP", "use": "sig"}]}
\.


--
-- Data for Name: parameters; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.parameters (key, value, created_at, updated_at) FROM stdin;
cluster_id	24504026-9658-4b2b-98ff-d561bdd261c4	\N	2025-07-04 04:11:11+00
\.


--
-- Data for Name: partials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.partials (id, created_at, updated_at, type, config, ws_id, name, tags) FROM stdin;
\.


--
-- Data for Name: plugins; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.plugins (id, created_at, name, consumer_id, service_id, route_id, config, enabled, cache_key, protocols, tags, ws_id, instance_name, updated_at, ordering, consumer_group_id) FROM stdin;
c1528032-5303-4b4a-9778-bc3b714880e2	2025-07-04 04:47:09+00	jwt	\N	09c63f6b-3d75-4aef-8fec-6f0852981139	f09bab29-ab14-455e-8d8c-11789a25bbc6	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 36000}	t	plugins:jwt:f09bab29-ab14-455e-8d8c-11789a25bbc6:09c63f6b-3d75-4aef-8fec-6f0852981139:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-04 08:51:40+00	\N	\N
b81a9059-dbd0-4021-8db6-13be43abc257	2025-07-07 14:24:51+00	jwt	\N	1251a788-a665-47b1-87ec-ed342c1c82f4	b4688d68-6d8e-4d4b-96d9-e43a5a577571	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:b4688d68-6d8e-4d4b-96d9-e43a5a577571:1251a788-a665-47b1-87ec-ed342c1c82f4:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:24:51+00	\N	\N
c49327f3-6859-46bf-b0d4-d7419a3cda78	2025-07-07 14:25:24+00	jwt	\N	1251a788-a665-47b1-87ec-ed342c1c82f4	cb07ef9c-b152-4550-9d46-4b6006aa6edc	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:cb07ef9c-b152-4550-9d46-4b6006aa6edc:1251a788-a665-47b1-87ec-ed342c1c82f4:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:25:24+00	\N	\N
2a633512-22d9-473e-8e97-e699103ee3e8	2025-07-07 14:26:10+00	jwt	\N	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	634d4640-a750-4550-8009-f13b3e4fc73a	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:634d4640-a750-4550-8009-f13b3e4fc73a:c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:26:10+00	\N	\N
641d1b1b-424d-4f54-8bf9-d4411335c019	2025-07-07 14:26:52+00	jwt	\N	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	cf591182-8f90-48b0-b028-78424689dede	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:cf591182-8f90-48b0-b028-78424689dede:c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:26:52+00	\N	\N
0550ea50-0a5b-4f9e-803d-15e9a53f5431	2025-07-07 14:37:39+00	jwt	\N	566e4d9f-cd2b-45d3-93ae-a8e039557755	e366c315-e7e5-4dbb-8644-46a19d71028b	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:e366c315-e7e5-4dbb-8644-46a19d71028b:566e4d9f-cd2b-45d3-93ae-a8e039557755:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:37:39+00	\N	\N
6e5723d0-16f8-4be4-a02f-27ee7d3ed35a	2025-07-07 14:39:11+00	jwt	\N	566e4d9f-cd2b-45d3-93ae-a8e039557755	3624cbd4-6251-421c-90b1-48ae2cea077e	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:3624cbd4-6251-421c-90b1-48ae2cea077e:566e4d9f-cd2b-45d3-93ae-a8e039557755:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:39:11+00	\N	\N
ce5f4357-1adb-483c-b422-29a6f3e4083c	2025-07-07 14:39:41+00	jwt	\N	566e4d9f-cd2b-45d3-93ae-a8e039557755	32e51780-7443-4383-a77f-2b8083785450	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:32e51780-7443-4383-a77f-2b8083785450:566e4d9f-cd2b-45d3-93ae-a8e039557755:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:39:41+00	\N	\N
fb6d7990-d804-46c9-b8d2-50fbf4aa39da	2025-07-07 14:40:14+00	jwt	\N	566e4d9f-cd2b-45d3-93ae-a8e039557755	35ed8c30-dcba-4a92-bdba-d7b5d5ff763c	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:35ed8c30-dcba-4a92-bdba-d7b5d5ff763c:566e4d9f-cd2b-45d3-93ae-a8e039557755:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:40:14+00	\N	\N
39f35ea2-b767-4b3c-b290-16310e5af9c7	2025-07-07 14:44:43+00	jwt	\N	87370e49-d53c-429c-ae8c-4ce17005a1f2	e5958952-0ea5-4266-a511-381b4b63df0b	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:e5958952-0ea5-4266-a511-381b4b63df0b:87370e49-d53c-429c-ae8c-4ce17005a1f2:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:44:43+00	\N	\N
b6c3bf14-fd10-4383-84bd-bb28fb525e01	2025-07-07 14:46:02+00	jwt	\N	2889026b-29b2-4773-89cf-68de38288802	451b1697-9aa5-40f6-bbb7-bb9d2b0cf7e1	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:451b1697-9aa5-40f6-bbb7-bb9d2b0cf7e1:2889026b-29b2-4773-89cf-68de38288802:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:46:02+00	\N	\N
2de57127-7706-4291-9543-a086d099405e	2025-07-07 14:49:43+00	jwt	\N	f1d809f8-9551-4af2-8339-1491333734bc	888806ff-b8a3-404c-96c4-45dd3d6bd0b9	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:888806ff-b8a3-404c-96c4-45dd3d6bd0b9:f1d809f8-9551-4af2-8339-1491333734bc:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:49:43+00	\N	\N
8f1ed0e8-3464-4652-9af1-24cbd09825da	2025-07-07 14:49:56+00	jwt	\N	f1d809f8-9551-4af2-8339-1491333734bc	c5e48fa3-7283-4314-b7a5-e4b8569b27be	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:c5e48fa3-7283-4314-b7a5-e4b8569b27be:f1d809f8-9551-4af2-8339-1491333734bc:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:49:56+00	\N	\N
88375331-971c-4d2f-ad7f-e561e92ca796	2025-07-07 14:50:10+00	jwt	\N	f1d809f8-9551-4af2-8339-1491333734bc	049e5200-2412-44cc-b0cf-1222375d4731	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:049e5200-2412-44cc-b0cf-1222375d4731:f1d809f8-9551-4af2-8339-1491333734bc:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:50:10+00	\N	\N
b5783fc5-51c3-409a-8c1c-27970425490b	2025-07-07 14:50:25+00	jwt	\N	f1d809f8-9551-4af2-8339-1491333734bc	02880504-df21-4cbe-9e19-5bfbb8a33f66	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:02880504-df21-4cbe-9e19-5bfbb8a33f66:f1d809f8-9551-4af2-8339-1491333734bc:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:50:25+00	\N	\N
ef0850d2-940a-47b7-bfb0-c1dd645b9c10	2025-07-07 14:51:56+00	jwt	\N	f1d809f8-9551-4af2-8339-1491333734bc	4f36c05e-138c-45d2-b5aa-b48848bcd328	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:4f36c05e-138c-45d2-b5aa-b48848bcd328:f1d809f8-9551-4af2-8339-1491333734bc:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:51:56+00	\N	\N
d98625fd-afb9-42b9-803d-c1f1837b7cd7	2025-07-07 14:53:54+00	jwt	\N	f1d809f8-9551-4af2-8339-1491333734bc	df3ac65a-4277-4466-b33c-653e7e2b38f9	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:df3ac65a-4277-4466-b33c-653e7e2b38f9:f1d809f8-9551-4af2-8339-1491333734bc:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:53:54+00	\N	\N
db4d3219-7e68-4924-9801-9f08bacba06e	2025-07-07 14:54:10+00	jwt	\N	f1d809f8-9551-4af2-8339-1491333734bc	ea765f03-e3c9-4d61-8dd2-01d9859acc0f	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:ea765f03-e3c9-4d61-8dd2-01d9859acc0f:f1d809f8-9551-4af2-8339-1491333734bc:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:54:10+00	\N	\N
2a150635-3836-418b-aab1-d643f113eb7b	2025-07-07 14:54:27+00	jwt	\N	f1d809f8-9551-4af2-8339-1491333734bc	7f57621a-7e31-468b-87b8-dc272e39194a	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:7f57621a-7e31-468b-87b8-dc272e39194a:f1d809f8-9551-4af2-8339-1491333734bc:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:54:27+00	\N	\N
355cae7e-2269-4c57-bd0c-c1cd554af7e5	2025-07-07 14:55:10+00	jwt	\N	1251a788-a665-47b1-87ec-ed342c1c82f4	13c18cc8-eaa7-451f-a5d6-0b516ce89183	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:13c18cc8-eaa7-451f-a5d6-0b516ce89183:1251a788-a665-47b1-87ec-ed342c1c82f4:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:55:10+00	\N	\N
45bd3a97-d7b4-4ddd-9044-0c3ab611fdf4	2025-07-07 14:56:21+00	jwt	\N	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	5d611f2e-fb2e-4095-8d64-6d95190ad0d6	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:5d611f2e-fb2e-4095-8d64-6d95190ad0d6:c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:56:21+00	\N	\N
5963bbfd-d4ed-4399-acc3-2d45e4df18c9	2025-07-07 14:57:32+00	jwt	\N	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	b69833d2-074e-4dc7-b7ac-15ab1ff4de63	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:b69833d2-074e-4dc7-b7ac-15ab1ff4de63:c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:57:32+00	\N	\N
d53f76e4-4010-4b7b-ac4f-83117b593ada	2025-07-07 14:58:56+00	jwt	\N	2889026b-29b2-4773-89cf-68de38288802	0ad4d860-59df-4c94-83d1-0f2e39b64fd0	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:0ad4d860-59df-4c94-83d1-0f2e39b64fd0:2889026b-29b2-4773-89cf-68de38288802:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-07 14:58:56+00	\N	\N
279a1aa6-776f-4fd5-b55e-b4f9cbf7551e	2025-07-08 10:06:10+00	jwt	\N	1251a788-a665-47b1-87ec-ed342c1c82f4	ff4b8e7b-f9c1-4d3d-b5ad-6864c559abf3	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	t	plugins:jwt:ff4b8e7b-f9c1-4d3d-b5ad-6864c559abf3:1251a788-a665-47b1-87ec-ed342c1c82f4:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-08 10:06:10+00	\N	\N
c070626a-1a38-4d41-a642-f3d864490ce5	2025-07-07 14:45:19+00	jwt	\N	2889026b-29b2-4773-89cf-68de38288802	25fd9f23-01ad-40ee-ad3a-b568a1f3ab0c	{"realm": null, "anonymous": null, "cookie_names": [], "header_names": ["authorization"], "key_claim_name": "iss", "uri_param_names": ["jwt"], "claims_to_verify": null, "run_on_preflight": true, "secret_is_base64": false, "maximum_expiration": 0}	f	plugins:jwt:25fd9f23-01ad-40ee-ad3a-b568a1f3ab0c:2889026b-29b2-4773-89cf-68de38288802:::52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	{grpc,grpcs,http,https}	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	\N	2025-07-08 11:50:47+00	\N	\N
\.


--
-- Data for Name: plugins_partials; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.plugins_partials (id, created_at, updated_at, path, plugin_id, partial_id) FROM stdin;
\.


--
-- Data for Name: ratelimiting_metrics; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.ratelimiting_metrics (identifier, period, period_date, service_id, route_id, value, ttl) FROM stdin;
\.


--
-- Data for Name: rbac_role_endpoints; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.rbac_role_endpoints (role_id, workspace, endpoint, actions, comment, created_at, negative, updated_at) FROM stdin;
76ebc14d-89de-4f3b-9649-2e1ce1b912a5	*	*	1	\N	2025-07-04 04:11:10+00	f	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	*	15	\N	2025-07-04 04:11:10+00	f	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/rbac/*	15	\N	2025-07-04 04:11:10+00	t	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/rbac/*/*	15	\N	2025-07-04 04:11:10+00	t	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/rbac/*/*/*	15	\N	2025-07-04 04:11:10+00	t	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/rbac/*/*/*/*	15	\N	2025-07-04 04:11:10+00	t	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/rbac/*/*/*/*/*	15	\N	2025-07-04 04:11:10+00	t	2025-07-04 04:11:11+00
9c472dfc-dfa0-4486-86cd-7a6ecc91a561	*	*	15	\N	2025-07-04 04:11:10+00	f	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/admins	15	\N	2025-07-04 04:11:11+00	t	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/admins/*	15	\N	2025-07-04 04:11:11+00	t	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/groups	15	\N	2025-07-04 04:11:11+00	t	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	*	/groups/*	15	\N	2025-07-04 04:11:11+00	t	2025-07-04 04:11:11+00
\.


--
-- Data for Name: rbac_role_entities; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.rbac_role_entities (role_id, entity_id, entity_type, actions, negative, comment, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: rbac_roles; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.rbac_roles (id, name, comment, created_at, is_default, ws_id, updated_at) FROM stdin;
76ebc14d-89de-4f3b-9649-2e1ce1b912a5	read-only	Read access to all endpoints, across all workspaces	2025-07-04 04:11:10+00	f	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	2025-07-04 04:11:11+00
542fbef7-ac57-4033-98b2-27a23ad5e958	admin	Full access to all endpoints, across all workspaces—except RBAC Admin API	2025-07-04 04:11:10+00	f	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	2025-07-04 04:11:11+00
9c472dfc-dfa0-4486-86cd-7a6ecc91a561	super-admin	Full access to all endpoints, across all workspaces	2025-07-04 04:11:10+00	f	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	2025-07-04 04:11:11+00
b5d686e9-6abe-44c4-b433-415632204f21	kong_admin	Default user role generated for kong_admin	2025-07-04 04:11:10+00	t	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	2025-07-04 04:11:11+00
\.


--
-- Data for Name: rbac_user_groups; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.rbac_user_groups (user_id, group_id) FROM stdin;
\.


--
-- Data for Name: rbac_user_roles; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.rbac_user_roles (user_id, role_id, role_source) FROM stdin;
b0ca086d-ca49-4983-bd1c-ade4600f9cb4	9c472dfc-dfa0-4486-86cd-7a6ecc91a561	\N
b0ca086d-ca49-4983-bd1c-ade4600f9cb4	b5d686e9-6abe-44c4-b433-415632204f21	\N
\.


--
-- Data for Name: rbac_users; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.rbac_users (id, name, user_token, user_token_ident, comment, enabled, created_at, ws_id, updated_at) FROM stdin;
b0ca086d-ca49-4983-bd1c-ade4600f9cb4	kong_admin	test	\N	Initial RBAC Secure User	t	2025-07-04 04:11:10+00	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	2025-07-04 04:11:11+00
\.


--
-- Data for Name: response_ratelimiting_metrics; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.response_ratelimiting_metrics (identifier, period, period_date, service_id, route_id, value) FROM stdin;
\.


--
-- Data for Name: rl_counters; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.rl_counters (key, namespace, window_start, window_size, count) FROM stdin;
\.


--
-- Data for Name: routes; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.routes (id, created_at, updated_at, name, service_id, protocols, methods, hosts, paths, snis, sources, destinations, regex_priority, strip_path, preserve_host, tags, https_redirect_status_code, headers, path_handling, ws_id, request_buffering, response_buffering, expression, priority) FROM stdin;
f09bab29-ab14-455e-8d8c-11789a25bbc6	2025-07-04 04:18:12+00	2025-07-04 04:45:55+00	HelloCheck	09c63f6b-3d75-4aef-8fec-6f0852981139	{http,https}	\N	\N	{/Hello}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
be1f4247-befc-4e05-b68b-cd56fe2202f6	2025-07-04 04:54:03+00	2025-07-04 04:54:03+00	Register-doctor	09c63f6b-3d75-4aef-8fec-6f0852981139	{http,https}	\N	\N	{/api/v0/account/register/user/doctor}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
8c3c4378-2dfe-4684-9250-5a98ba8c7d35	2025-07-04 04:54:29+00	2025-07-04 04:54:45+00	Refresh-token	09c63f6b-3d75-4aef-8fec-6f0852981139	{http,https}	\N	\N	{/api/v0/account/refresh-token}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
9a524e50-7dc5-450b-83a8-bbb1260db9b6	2025-07-04 04:55:04+00	2025-07-04 04:59:28+00	Register-staff	09c63f6b-3d75-4aef-8fec-6f0852981139	{http,https}	\N	\N	{/api/v0/account/register/user/staff}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
f439a546-d5ae-4d32-868b-b48f7d1c6ca9	2025-07-04 05:26:23+00	2025-07-04 05:26:23+00	Login	09c63f6b-3d75-4aef-8fec-6f0852981139	{http,https}	\N	\N	{/api/v0/account/login}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
c8f80f12-725a-41d6-a487-d2de8d1984ab	2025-07-04 12:56:30+00	2025-07-04 12:56:30+00	SendAppointmentNotification	b900a1a8-86d3-436e-8ffc-2adb70024351	{http,https}	\N	\N	{/api/v0/Notification/send-appointment}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
4d099e06-9693-4b36-b87d-5d481b205cca	2025-07-07 13:19:57+00	2025-07-07 13:19:57+00	GetAllDoctor	2889026b-29b2-4773-89cf-68de38288802	{http,https}	{GET}	\N	{/api/v0/doctors}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
25fd9f23-01ad-40ee-ad3a-b568a1f3ab0c	2025-07-07 13:22:29+00	2025-07-07 13:22:29+00	CreateDoctor	2889026b-29b2-4773-89cf-68de38288802	{http,https}	{POST}	\N	{/api/v0/doctors/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
3c556e74-eef1-4826-a64f-e4bbb0615645	2025-07-07 13:23:22+00	2025-07-07 13:23:22+00	GetAvailableDoctors	2889026b-29b2-4773-89cf-68de38288802	{http,https}	{POST}	\N	{/api/v0/doctors/available}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
c2a75c7a-246f-487d-af2b-05b206b2ac49	2025-07-07 13:24:10+00	2025-07-07 13:24:10+00	GetDoctorsByDepartment	2889026b-29b2-4773-89cf-68de38288802	{http,https}	{POST}	\N	{/api/v0/doctors/byDepartment}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
0ad4d860-59df-4c94-83d1-0f2e39b64fd0	2025-07-07 13:25:19+00	2025-07-07 13:25:19+00	GetDoctorByID	2889026b-29b2-4773-89cf-68de38288802	{http,https}	{POST}	\N	{/api/v0/doctors/byID}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
451b1697-9aa5-40f6-bbb7-bb9d2b0cf7e1	2025-07-07 13:27:30+00	2025-07-07 13:27:30+00	GetDoctorByIdentity	2889026b-29b2-4773-89cf-68de38288802	{http,https}	{GET}	\N	{/api/v0/doctors/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
8bba9c02-bac6-4d94-a598-889e5252c7a5	2025-07-07 13:28:12+00	2025-07-07 13:28:12+00	UpdateDoctorByIdentity	2889026b-29b2-4773-89cf-68de38288802	{http,https}	{PUT}	\N	{/api/v0/doctors/byIdentity/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
8e7fd083-280f-4dab-923c-691237c54ab1	2025-07-07 13:30:07+00	2025-07-07 13:30:07+00	GetAllAppointments	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	{http,https}	{GET}	\N	{/api/v0/appointments/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
b69833d2-074e-4dc7-b7ac-15ab1ff4de63	2025-07-07 13:32:34+00	2025-07-07 13:32:34+00	GetAppointmentsByPatientIdentity	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	{http,https}	{GET}	\N	{/api/v0/appointments/byPatientIdentity}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
634d4640-a750-4550-8009-f13b3e4fc73a	2025-07-07 13:31:43+00	2025-07-07 13:32:47+00	GetAppointmentsByDoctorId	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	{http,https}	{GET}	\N	{/api/v0/appointments/byDoctorId/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
5d611f2e-fb2e-4095-8d64-6d95190ad0d6	2025-07-07 13:40:13+00	2025-07-07 13:40:13+00	UpdateAppointment	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	{http,https}	{PUT}	\N	{/api/v0/appointments/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
49d34e4b-6472-4357-b6c3-ad1f4519741b	2025-07-07 13:39:02+00	2025-07-07 13:40:24+00	GetAppoinmentByID	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	{http,https}	{GET}	\N	{/api/v0/appointments/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
cf591182-8f90-48b0-b028-78424689dede	2025-07-07 13:48:00+00	2025-07-07 13:48:00+00	CreateAppointment	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	{http,https}	{POST}	\N	{/api/v0/appointments/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
10f9a247-7e78-465a-9775-ff4fd2e3ddb5	2025-07-07 13:50:08+00	2025-07-07 13:50:08+00	GetAvailableDoctorsByDate	c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	{http,https}	{POST}	\N	{/api/v0/appointments/availableDoctors}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
ff4b8e7b-f9c1-4d3d-b5ad-6864c559abf3	2025-07-07 14:19:54+00	2025-07-07 14:19:54+00	GetAllPatients	1251a788-a665-47b1-87ec-ed342c1c82f4	{http,https}	{GET}	\N	{/api/v0/patients/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
13c18cc8-eaa7-451f-a5d6-0b516ce89183	2025-07-07 14:21:13+00	2025-07-07 14:21:13+00	GetPatientByIdentity	1251a788-a665-47b1-87ec-ed342c1c82f4	{http,https}	{GET}	\N	{/api/v0/patients/byIdentity}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
294842af-4f78-4f2b-9143-e741b2caa505	2025-07-07 14:22:30+00	2025-07-07 14:22:30+00	GetPatientById	1251a788-a665-47b1-87ec-ed342c1c82f4	{http,https}	{GET}	\N	{/api/v0/patients/byId}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
cb07ef9c-b152-4550-9d46-4b6006aa6edc	2025-07-07 14:22:52+00	2025-07-07 14:22:52+00	CreatePatient	1251a788-a665-47b1-87ec-ed342c1c82f4	{http,https}	{POST}	\N	{/api/v0/patients/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
b4688d68-6d8e-4d4b-96d9-e43a5a577571	2025-07-07 14:24:06+00	2025-07-07 14:24:06+00	UpdatePatient	1251a788-a665-47b1-87ec-ed342c1c82f4	{http,https}	{PUT}	\N	{/api/v0/patients/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
e366c315-e7e5-4dbb-8644-46a19d71028b	2025-07-07 14:29:39+00	2025-07-07 14:29:39+00	GetAllMedicines	566e4d9f-cd2b-45d3-93ae-a8e039557755	{http,https}	{GET}	\N	{/api/v0/medicines/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
1bdcffac-71d1-4cd0-8deb-94700784e810	2025-07-07 14:30:38+00	2025-07-07 14:30:38+00	GetMedicinesNearExpiry	566e4d9f-cd2b-45d3-93ae-a8e039557755	{http,https}	{GET}	\N	{/api/v0/medicines/near-expiry}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
32e51780-7443-4383-a77f-2b8083785450	2025-07-07 14:31:13+00	2025-07-07 14:31:13+00	GetMedicinesByName	566e4d9f-cd2b-45d3-93ae-a8e039557755	{http,https}	{GET}	\N	{/api/v0/medicines/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
58823f6c-ed0c-410a-9475-7bfad70b32d5	2025-07-07 14:31:33+00	2025-07-07 14:31:33+00	GetMedicineByID	566e4d9f-cd2b-45d3-93ae-a8e039557755	{http,https}	{GET}	\N	{/api/v0/medicines/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
35ed8c30-dcba-4a92-bdba-d7b5d5ff763c	2025-07-07 14:32:58+00	2025-07-07 14:32:58+00	CreateMedicine	566e4d9f-cd2b-45d3-93ae-a8e039557755	{http,https}	{POST}	\N	{/api/v0/medicines/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
3624cbd4-6251-421c-90b1-48ae2cea077e	2025-07-07 14:35:41+00	2025-07-07 14:35:41+00	DeleteMedicine	566e4d9f-cd2b-45d3-93ae-a8e039557755	{http,https}	{DELETE}	\N	{/api/v0/medicines/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
a29c7e63-f286-40e0-820c-32ec9c2b61c1	2025-07-07 14:36:50+00	2025-07-07 14:36:50+00	UpdateMedicine	566e4d9f-cd2b-45d3-93ae-a8e039557755	{http,https}	{PUT}	\N	{/api/v0/medicines/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
a5ba9b80-b09e-4844-b029-0a951f22d41e	2025-07-07 14:42:25+00	2025-07-07 14:42:25+00	GetStaffByID	87370e49-d53c-429c-ae8c-4ce17005a1f2	{http,https}	{POST}	\N	{/api/v0/staff/byID}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
71cf838e-7a3f-4311-be8e-6e3c18682ff3	2025-07-07 14:42:45+00	2025-07-07 14:42:45+00	GetAllStaff	87370e49-d53c-429c-ae8c-4ce17005a1f2	{http,https}	{GET}	\N	{/api/v0/staff/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
ffce2cbb-dc33-46a0-91cb-1172a8367475	2025-07-07 14:43:29+00	2025-07-07 14:43:29+00	GetStaffByIdentity	87370e49-d53c-429c-ae8c-4ce17005a1f2	{http,https}	{GET}	\N	{/api/v0/staff/byIdentity/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
e5958952-0ea5-4266-a511-381b4b63df0b	2025-07-07 14:43:48+00	2025-07-07 14:43:48+00	CreateStaff	87370e49-d53c-429c-ae8c-4ce17005a1f2	{http,https}	{POST}	\N	{/api/v0/staff/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
a170aa83-5761-4d38-9317-42f5130252b0	2025-07-07 14:44:22+00	2025-07-07 14:44:22+00	UpdateStaff	87370e49-d53c-429c-ae8c-4ce17005a1f2	{http,https}	{PUT}	\N	{/api/v0/staff/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
888806ff-b8a3-404c-96c4-45dd3d6bd0b9	2025-07-07 14:47:46+00	2025-07-07 14:47:46+00	DeletePrescriptionDetail	f1d809f8-9551-4af2-8339-1491333734bc	{http,https}	{DELETE}	\N	{/api/v0/prescription-details/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
049e5200-2412-44cc-b0cf-1222375d4731	2025-07-07 14:48:23+00	2025-07-07 14:48:23+00	CreatePrescriptionDetail	f1d809f8-9551-4af2-8339-1491333734bc	{http,https}	{POST}	\N	{/api/v0/prescription-details/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
02880504-df21-4cbe-9e19-5bfbb8a33f66	2025-07-07 14:48:59+00	2025-07-07 14:48:59+00	UpdatePrescriptionDetail	f1d809f8-9551-4af2-8339-1491333734bc	{http,https}	{PUT}	\N	{/api/v0/prescription-details/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
c5e48fa3-7283-4314-b7a5-e4b8569b27be	2025-07-07 14:49:26+00	2025-07-07 14:49:26+00	GetAllPrescriptionDetails	f1d809f8-9551-4af2-8339-1491333734bc	{http,https}	{GET}	\N	{/api/v0/prescription-details/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
4f36c05e-138c-45d2-b5aa-b48848bcd328	2025-07-07 14:51:39+00	2025-07-07 14:51:39+00	GetAllPrescriptions	f1d809f8-9551-4af2-8339-1491333734bc	{http,https}	{GET}	\N	{/api/v0/prescriptions/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
df3ac65a-4277-4466-b33c-653e7e2b38f9	2025-07-07 14:52:41+00	2025-07-07 14:52:41+00	GetPrescriptionByAppointmentID	f1d809f8-9551-4af2-8339-1491333734bc	{http,https}	{GET}	\N	{/api/v0/prescriptions/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
7f57621a-7e31-468b-87b8-dc272e39194a	2025-07-07 14:53:04+00	2025-07-07 14:53:04+00	CreatePrescription	f1d809f8-9551-4af2-8339-1491333734bc	{http,https}	{POST}	\N	{/api/v0/prescriptions/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
ea765f03-e3c9-4d61-8dd2-01d9859acc0f	2025-07-07 14:53:26+00	2025-07-07 14:53:26+00	UpdatePrescription	f1d809f8-9551-4af2-8339-1491333734bc	{http,https}	{PUT}	\N	{/api/v0/prescriptions/}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
4514b3d2-98ad-40ab-b5f4-f9733d37ac0e	2025-07-08 19:03:51+00	2025-07-09 07:08:26+00	monthly-patient-statistics	c6fe6245-b8f7-4605-b274-9e27d8322d00	{http,https}	{GET}	\N	{/api/v0/Report/monthly-patient-statistics}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
61a72aa0-828f-423b-8a04-f0ceaf99e8b9	2025-07-08 19:03:08+00	2025-07-09 07:09:04+00	monthly-prescription-statistics	c6fe6245-b8f7-4605-b274-9e27d8322d00	{http,https}	{GET}	\N	{/api/v0/Report/monthly-prescription-statistics}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
533377d5-afa4-4d68-9e65-0cef20d3da42	2025-07-04 12:57:10+00	2025-07-09 07:11:05+00	SendPrescriptionReadyNotification	b900a1a8-86d3-436e-8ffc-2adb70024351	{http,https}	{POST}	\N	{/api/v0/Notification/send-prescription-ready}	\N	\N	\N	0	f	f	{}	426	\N	v0	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t	t	\N	\N
\.


--
-- Data for Name: schema_meta; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.schema_meta (key, subsystem, last_executed, executed, pending) FROM stdin;
schema_meta	graphql-proxy-cache-advanced	002_370_to_380	{001_370_to_380,002_370_to_380}	{}
schema_meta	pre-function	001_280_to_300	{001_280_to_300}	{}
schema_meta	core	025_390_to_3100	{000_base,003_100_to_110,004_110_to_120,005_120_to_130,006_130_to_140,007_140_to_150,008_150_to_200,009_200_to_210,010_210_to_211,011_212_to_213,012_213_to_220,013_220_to_230,014_230_to_260,015_260_to_270,016_270_to_280,016_280_to_300,017_300_to_310,018_310_to_320,019_320_to_330,020_330_to_340,021_340_to_350,022_350_to_360,023_360_to_370,024_380_to_390,025_390_to_3100}	{}
schema_meta	mtls-auth	002_2200_to_2300	{000_base_mtls_auth,001_200_to_210,002_2200_to_2300}	{}
schema_meta	graphql-rate-limiting-advanced	002_370_to_380	{000_base_gql_rate_limiting,001_370_to_380,002_370_to_380}	{}
schema_meta	header-cert-auth	000_base_header_cert_auth	{000_base_header_cert_auth}	\N
schema_meta	acl	004_212_to_213	{000_base_acl,002_130_to_140,003_200_to_210,004_212_to_213}	{}
schema_meta	response-ratelimiting	001_350_to_360	{000_base_response_rate_limiting,001_350_to_360}	{}
schema_meta	hmac-auth	003_200_to_210	{000_base_hmac_auth,002_130_to_140,003_200_to_210}	{}
schema_meta	acme	003_350_to_360	{000_base_acme,001_280_to_300,002_320_to_330,003_350_to_360}	{}
schema_meta	ai-proxy	001_360_to_370	{001_360_to_370}	{}
schema_meta	http-log	001_280_to_300	{001_280_to_300}	{}
schema_meta	ai-rate-limiting-advanced	003_390_to_3100	{001_370_to_380,002_370_to_380,003_390_to_3100}	{}
schema_meta	ip-restriction	001_200_to_210	{001_200_to_210}	{}
schema_meta	basic-auth	003_200_to_210	{000_base_basic_auth,002_130_to_140,003_200_to_210}	{}
schema_meta	bot-detection	001_200_to_210	{001_200_to_210}	{}
schema_meta	canary	001_200_to_210	{001_200_to_210}	{}
schema_meta	degraphql	000_base	{000_base}	\N
schema_meta	proxy-cache-advanced	002_370_to_380	{001_370_to_380,002_370_to_380}	{}
schema_meta	jwt	003_200_to_210	{000_base_jwt,002_130_to_140,003_200_to_210}	{}
schema_meta	jwt-signer	001_200_to_210	{000_base_jwt_signer,001_200_to_210}	\N
schema_meta	saml	001_370_to_380	{001_370_to_380}	{}
schema_meta	oauth2	007_320_to_330	{000_base_oauth2,003_130_to_140,004_200_to_210,005_210_to_211,006_320_to_330,007_320_to_330}	{}
schema_meta	key-auth	004_320_to_330	{000_base_key_auth,002_130_to_140,003_200_to_210,004_320_to_330}	{}
schema_meta	key-auth-enc	001_200_to_210	{000_base_key_auth_enc,001_200_to_210}	{}
schema_meta	enterprise	023_3900_to_31000_4	{000_base,006_1301_to_1500,006_1301_to_1302,010_1500_to_2100,007_1500_to_1504,008_1504_to_1505,007_1500_to_2100,009_1506_to_1507,009_2100_to_2200,010_2200_to_2211,010_2200_to_2300,010_2200_to_2300_1,011_2300_to_2600,012_2600_to_2700,012_2600_to_2700_1,013_2700_to_2800,014_2800_to_3000,015_3000_to_3100,016_3100_to_3200,017_3200_to_3300,018_3300_to_3400,019_3500_to_3600,020_3600_to_3700,021_3700_to_3800,021_3700_to_3800_1,022_3800_to_3900,023_3900_to_31000,023_3900_to_31000_1,023_3900_to_31000_2,023_3900_to_31000_3,023_3900_to_31000_4}	{}
schema_meta	enterprise.acl	001_1500_to_2100	{001_1500_to_2100}	{}
schema_meta	rate-limiting	006_350_to_360	{000_base_rate_limiting,003_10_to_112,004_200_to_210,005_320_to_330,006_350_to_360}	{}
schema_meta	konnect-application-auth	004_exhausted_scopes_addition	{000_base_konnect_applications,001_consumer_group_addition,002_strategy_id_addition,003_application_context,004_exhausted_scopes_addition}	\N
schema_meta	session	003_330_to_3100	{000_base_session,001_add_ttl_index,002_320_to_330,003_330_to_3100}	\N
schema_meta	openid-connect	004_370_to_380	{000_base_openid_connect,001_14_to_15,002_200_to_210,003_280_to_300,004_370_to_380}	{}
schema_meta	opentelemetry	001_331_to_332	{001_331_to_332}	{}
schema_meta	post-function	001_280_to_300	{001_280_to_300}	{}
schema_meta	rate-limiting-advanced	002_370_to_380	{001_370_to_380,002_370_to_380}	{}
schema_meta	enterprise.acme	001_3900_to_31000	{001_3900_to_31000}	\N
schema_meta	vault-auth	002_300_to_310	{000_base_vault_auth,001_280_to_300,002_300_to_310}	\N
schema_meta	enterprise.basic-auth	001_1500_to_2100	{001_1500_to_2100}	{}
schema_meta	enterprise.hmac-auth	001_1500_to_2100	{001_1500_to_2100}	{}
schema_meta	enterprise.jwt	001_1500_to_2100	{001_1500_to_2100}	{}
schema_meta	enterprise.key-auth	002_3900_to_31000	{001_1500_to_2100,002_3900_to_31000}	{}
schema_meta	enterprise.key-auth-enc	003_3900_to_31000	{001_1500_to_2100,002_3100_to_3200,002_2800_to_3200,003_3900_to_31000}	{}
schema_meta	enterprise.mtls-auth	002_2200_to_2300	{001_1500_to_2100,002_2200_to_2300}	{}
schema_meta	enterprise.oauth2	002_2200_to_2211	{001_1500_to_2100,002_2200_to_2211}	{}
schema_meta	enterprise.request-transformer-advanced	001_1500_to_2100	{001_1500_to_2100}	{}
schema_meta	enterprise.response-transformer-advanced	001_1500_to_2100	{001_1500_to_2100}	{}
\.


--
-- Data for Name: services; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.services (id, created_at, updated_at, name, retries, protocol, host, port, path, connect_timeout, write_timeout, read_timeout, tags, client_certificate_id, tls_verify, tls_verify_depth, ca_certificates, ws_id, enabled) FROM stdin;
09c63f6b-3d75-4aef-8fec-6f0852981139	2025-07-04 04:17:32+00	2025-07-04 05:20:36+00	Identity	5	http	138.197.142.55	8080	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
b900a1a8-86d3-436e-8ffc-2adb70024351	2025-07-04 12:54:58+00	2025-07-04 12:54:58+00	Notification	5	http	138.197.142.55	5088	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
2889026b-29b2-4773-89cf-68de38288802	2025-07-07 13:16:31+00	2025-07-07 13:16:31+00	Doctor	5	http	138.197.142.55	5003	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	2025-07-07 13:28:49+00	2025-07-07 13:28:49+00	Appointment	5	http	138.197.142.55	5004	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
1251a788-a665-47b1-87ec-ed342c1c82f4	2025-07-07 14:13:13+00	2025-07-07 14:13:13+00	Patient	5	http	138.197.142.55	5001	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
566e4d9f-cd2b-45d3-93ae-a8e039557755	2025-07-07 14:28:45+00	2025-07-07 14:28:45+00	Medicine	5	http	138.197.142.55	5002	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
87370e49-d53c-429c-ae8c-4ce17005a1f2	2025-07-07 14:41:24+00	2025-07-07 14:41:24+00	Staff	5	http	138.197.142.55	5005	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
f1d809f8-9551-4af2-8339-1491333734bc	2025-07-07 14:47:09+00	2025-07-07 14:47:09+00	Prescription	5	http	138.197.142.55	5006	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
c6fe6245-b8f7-4605-b274-9e27d8322d00	2025-07-08 19:01:12+00	2025-07-08 19:01:12+00	ReportService	5	http	138.197.142.55	5163	/	60000	60000	60000	\N	\N	\N	\N	\N	52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	t
\.


--
-- Data for Name: session_metadatas; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.session_metadatas (id, session_id, sid, subject, audience, created_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.sessions (id, session_id, expires, data, created_at, ttl) FROM stdin;
\.


--
-- Data for Name: sm_vaults; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.sm_vaults (id, ws_id, prefix, name, description, config, created_at, updated_at, tags) FROM stdin;
\.


--
-- Data for Name: snis; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.snis (id, created_at, name, certificate_id, tags, ws_id, updated_at) FROM stdin;
\.


--
-- Data for Name: tags; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.tags (entity_id, entity_name, tags) FROM stdin;
7819c050-221b-47e1-b517-1df5963a1e55	consumers	\N
091eec81-6944-4d37-8484-afa74f191a35	basicauth_credentials	\N
f09bab29-ab14-455e-8d8c-11789a25bbc6	routes	{}
cea7ebd2-231b-41f1-8237-589f10df04cf	consumers	{}
eda76d6f-f017-40e6-9208-dd73bc4547c5	jwt_secrets	\N
be1f4247-befc-4e05-b68b-cd56fe2202f6	routes	{}
8c3c4378-2dfe-4684-9250-5a98ba8c7d35	routes	{}
9a524e50-7dc5-450b-83a8-bbb1260db9b6	routes	{}
09c63f6b-3d75-4aef-8fec-6f0852981139	services	\N
f439a546-d5ae-4d32-868b-b48f7d1c6ca9	routes	{}
c1528032-5303-4b4a-9778-bc3b714880e2	plugins	\N
b900a1a8-86d3-436e-8ffc-2adb70024351	services	\N
c8f80f12-725a-41d6-a487-d2de8d1984ab	routes	{}
2889026b-29b2-4773-89cf-68de38288802	services	\N
4d099e06-9693-4b36-b87d-5d481b205cca	routes	{}
25fd9f23-01ad-40ee-ad3a-b568a1f3ab0c	routes	{}
3c556e74-eef1-4826-a64f-e4bbb0615645	routes	{}
c2a75c7a-246f-487d-af2b-05b206b2ac49	routes	{}
0ad4d860-59df-4c94-83d1-0f2e39b64fd0	routes	{}
451b1697-9aa5-40f6-bbb7-bb9d2b0cf7e1	routes	{}
8bba9c02-bac6-4d94-a598-889e5252c7a5	routes	{}
c048f8a8-2fbf-4e4d-9d72-442ebfbdacc1	services	\N
8e7fd083-280f-4dab-923c-691237c54ab1	routes	{}
b69833d2-074e-4dc7-b7ac-15ab1ff4de63	routes	{}
634d4640-a750-4550-8009-f13b3e4fc73a	routes	{}
5d611f2e-fb2e-4095-8d64-6d95190ad0d6	routes	{}
49d34e4b-6472-4357-b6c3-ad1f4519741b	routes	{}
cf591182-8f90-48b0-b028-78424689dede	routes	{}
10f9a247-7e78-465a-9775-ff4fd2e3ddb5	routes	{}
1251a788-a665-47b1-87ec-ed342c1c82f4	services	\N
ff4b8e7b-f9c1-4d3d-b5ad-6864c559abf3	routes	{}
13c18cc8-eaa7-451f-a5d6-0b516ce89183	routes	{}
294842af-4f78-4f2b-9143-e741b2caa505	routes	{}
cb07ef9c-b152-4550-9d46-4b6006aa6edc	routes	{}
b4688d68-6d8e-4d4b-96d9-e43a5a577571	routes	{}
b81a9059-dbd0-4021-8db6-13be43abc257	plugins	\N
c49327f3-6859-46bf-b0d4-d7419a3cda78	plugins	\N
2a633512-22d9-473e-8e97-e699103ee3e8	plugins	\N
641d1b1b-424d-4f54-8bf9-d4411335c019	plugins	\N
566e4d9f-cd2b-45d3-93ae-a8e039557755	services	\N
e366c315-e7e5-4dbb-8644-46a19d71028b	routes	{}
1bdcffac-71d1-4cd0-8deb-94700784e810	routes	{}
32e51780-7443-4383-a77f-2b8083785450	routes	{}
58823f6c-ed0c-410a-9475-7bfad70b32d5	routes	{}
35ed8c30-dcba-4a92-bdba-d7b5d5ff763c	routes	{}
3624cbd4-6251-421c-90b1-48ae2cea077e	routes	{}
a29c7e63-f286-40e0-820c-32ec9c2b61c1	routes	{}
0550ea50-0a5b-4f9e-803d-15e9a53f5431	plugins	\N
6e5723d0-16f8-4be4-a02f-27ee7d3ed35a	plugins	\N
ce5f4357-1adb-483c-b422-29a6f3e4083c	plugins	\N
fb6d7990-d804-46c9-b8d2-50fbf4aa39da	plugins	\N
87370e49-d53c-429c-ae8c-4ce17005a1f2	services	\N
a5ba9b80-b09e-4844-b029-0a951f22d41e	routes	{}
71cf838e-7a3f-4311-be8e-6e3c18682ff3	routes	{}
ffce2cbb-dc33-46a0-91cb-1172a8367475	routes	{}
e5958952-0ea5-4266-a511-381b4b63df0b	routes	{}
a170aa83-5761-4d38-9317-42f5130252b0	routes	{}
39f35ea2-b767-4b3c-b290-16310e5af9c7	plugins	\N
b6c3bf14-fd10-4383-84bd-bb28fb525e01	plugins	\N
f1d809f8-9551-4af2-8339-1491333734bc	services	\N
888806ff-b8a3-404c-96c4-45dd3d6bd0b9	routes	{}
049e5200-2412-44cc-b0cf-1222375d4731	routes	{}
02880504-df21-4cbe-9e19-5bfbb8a33f66	routes	{}
c5e48fa3-7283-4314-b7a5-e4b8569b27be	routes	{}
2de57127-7706-4291-9543-a086d099405e	plugins	\N
8f1ed0e8-3464-4652-9af1-24cbd09825da	plugins	\N
88375331-971c-4d2f-ad7f-e561e92ca796	plugins	\N
b5783fc5-51c3-409a-8c1c-27970425490b	plugins	\N
4f36c05e-138c-45d2-b5aa-b48848bcd328	routes	{}
ef0850d2-940a-47b7-bfb0-c1dd645b9c10	plugins	\N
df3ac65a-4277-4466-b33c-653e7e2b38f9	routes	{}
7f57621a-7e31-468b-87b8-dc272e39194a	routes	{}
ea765f03-e3c9-4d61-8dd2-01d9859acc0f	routes	{}
d98625fd-afb9-42b9-803d-c1f1837b7cd7	plugins	\N
db4d3219-7e68-4924-9801-9f08bacba06e	plugins	\N
2a150635-3836-418b-aab1-d643f113eb7b	plugins	\N
355cae7e-2269-4c57-bd0c-c1cd554af7e5	plugins	\N
45bd3a97-d7b4-4ddd-9044-0c3ab611fdf4	plugins	\N
5963bbfd-d4ed-4399-acc3-2d45e4df18c9	plugins	\N
d53f76e4-4010-4b7b-ac4f-83117b593ada	plugins	\N
279a1aa6-776f-4fd5-b55e-b4f9cbf7551e	plugins	\N
c070626a-1a38-4d41-a642-f3d864490ce5	plugins	\N
c6fe6245-b8f7-4605-b274-9e27d8322d00	services	\N
4514b3d2-98ad-40ab-b5f4-f9733d37ac0e	routes	{}
61a72aa0-828f-423b-8a04-f0ceaf99e8b9	routes	{}
533377d5-afa4-4d68-9e65-0cef20d3da42	routes	{}
\.


--
-- Data for Name: targets; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.targets (id, created_at, upstream_id, target, weight, tags, ws_id, cache_key, updated_at) FROM stdin;
\.


--
-- Data for Name: upstreams; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.upstreams (id, created_at, name, hash_on, hash_fallback, hash_on_header, hash_fallback_header, hash_on_cookie, hash_on_cookie_path, slots, healthchecks, tags, algorithm, host_header, client_certificate_id, ws_id, hash_on_query_arg, hash_fallback_query_arg, hash_on_uri_capture, hash_fallback_uri_capture, use_srv_name, updated_at) FROM stdin;
\.


--
-- Data for Name: vault_auth_vaults; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vault_auth_vaults (id, created_at, updated_at, name, protocol, host, port, mount, vault_token, kv) FROM stdin;
\.


--
-- Data for Name: vaults; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vaults (id, created_at, updated_at, name, protocol, host, port, mount, vault_token) FROM stdin;
\.


--
-- Data for Name: vitals_code_classes_by_cluster; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_code_classes_by_cluster (code_class, at, duration, count) FROM stdin;
\.


--
-- Data for Name: vitals_code_classes_by_workspace; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_code_classes_by_workspace (workspace_id, code_class, at, duration, count) FROM stdin;
\.


--
-- Data for Name: vitals_codes_by_consumer_route; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_codes_by_consumer_route (consumer_id, service_id, route_id, code, at, duration, count) FROM stdin;
\.


--
-- Data for Name: vitals_codes_by_route; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_codes_by_route (service_id, route_id, code, at, duration, count) FROM stdin;
\.


--
-- Data for Name: vitals_locks; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_locks (key, expiry) FROM stdin;
delete_status_codes	\N
\.


--
-- Data for Name: vitals_node_meta; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_node_meta (node_id, first_report, last_report, hostname) FROM stdin;
\.


--
-- Data for Name: vitals_stats_days; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_stats_days (node_id, at, l2_hit, l2_miss, plat_min, plat_max, ulat_min, ulat_max, requests, plat_count, plat_total, ulat_count, ulat_total) FROM stdin;
\.


--
-- Data for Name: vitals_stats_hours; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_stats_hours (at, l2_hit, l2_miss, plat_min, plat_max) FROM stdin;
\.


--
-- Data for Name: vitals_stats_minutes; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_stats_minutes (node_id, at, l2_hit, l2_miss, plat_min, plat_max, ulat_min, ulat_max, requests, plat_count, plat_total, ulat_count, ulat_total) FROM stdin;
\.


--
-- Data for Name: vitals_stats_seconds; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.vitals_stats_seconds (node_id, at, l2_hit, l2_miss, plat_min, plat_max, ulat_min, ulat_max, requests, plat_count, plat_total, ulat_count, ulat_total) FROM stdin;
\.


--
-- Data for Name: workspace_entities; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.workspace_entities (workspace_id, workspace_name, entity_id, entity_type, unique_field_name, unique_field_value) FROM stdin;
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	76ebc14d-89de-4f3b-9649-2e1ce1b912a5	rbac_roles	id	76ebc14d-89de-4f3b-9649-2e1ce1b912a5
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	76ebc14d-89de-4f3b-9649-2e1ce1b912a5	rbac_roles	name	default:read-only
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	542fbef7-ac57-4033-98b2-27a23ad5e958	rbac_roles	id	542fbef7-ac57-4033-98b2-27a23ad5e958
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	542fbef7-ac57-4033-98b2-27a23ad5e958	rbac_roles	name	default:admin
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	9c472dfc-dfa0-4486-86cd-7a6ecc91a561	rbac_roles	id	9c472dfc-dfa0-4486-86cd-7a6ecc91a561
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	9c472dfc-dfa0-4486-86cd-7a6ecc91a561	rbac_roles	name	default:super-admin
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	b0ca086d-ca49-4983-bd1c-ade4600f9cb4	rbac_users	id	b0ca086d-ca49-4983-bd1c-ade4600f9cb4
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	b0ca086d-ca49-4983-bd1c-ade4600f9cb4	rbac_users	name	kong_admin
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	b0ca086d-ca49-4983-bd1c-ade4600f9cb4	rbac_users	user_token	test
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	b5d686e9-6abe-44c4-b433-415632204f21	rbac_roles	id	b5d686e9-6abe-44c4-b433-415632204f21
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	b5d686e9-6abe-44c4-b433-415632204f21	rbac_roles	name	default:kong_admin
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	7819c050-221b-47e1-b517-1df5963a1e55	consumers	id	7819c050-221b-47e1-b517-1df5963a1e55
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	7819c050-221b-47e1-b517-1df5963a1e55	consumers	username	kong_admin
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	7819c050-221b-47e1-b517-1df5963a1e55	consumers	custom_id	\N
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	8e093d19-b0e6-4ef4-92b1-5b6b5ce12c16	admins	id	8e093d19-b0e6-4ef4-92b1-5b6b5ce12c16
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	8e093d19-b0e6-4ef4-92b1-5b6b5ce12c16	admins	username	kong_admin
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	8e093d19-b0e6-4ef4-92b1-5b6b5ce12c16	admins	custom_id	\N
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	8e093d19-b0e6-4ef4-92b1-5b6b5ce12c16	admins	email	\N
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	091eec81-6944-4d37-8484-afa74f191a35	basicauth_credentials	id	091eec81-6944-4d37-8484-afa74f191a35
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	091eec81-6944-4d37-8484-afa74f191a35	basicauth_credentials	username	kong_admin
\.


--
-- Data for Name: workspace_entity_counters; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.workspace_entity_counters (workspace_id, entity_type, count) FROM stdin;
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	basicauth_credentials	1
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	rbac_users	1
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	rbac_roles	4
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	consumers	1
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	jwt_secrets	1
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	plugins	25
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	services	9
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	routes	48
\.


--
-- Data for Name: workspaces; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.workspaces (id, name, comment, created_at, meta, config, updated_at) FROM stdin;
52490fa1-0cb5-4ac6-a0c7-d4ca986222f8	default	\N	2025-07-04 04:11:09+00	\N	\N	2025-07-04 04:11:09+00
\.


--
-- Data for Name: ws_migrations_backup; Type: TABLE DATA; Schema: public; Owner: kong
--

COPY public.ws_migrations_backup (entity_type, entity_id, unique_field_name, unique_field_value, created_at) FROM stdin;
basicauth_credentials	091eec81-6944-4d37-8484-afa74f191a35	username	default:kong_admin	2025-07-04 04:11:11+00
\.


--
-- Name: clustering_rpc_requests_id_seq; Type: SEQUENCE SET; Schema: public; Owner: kong
--

SELECT pg_catalog.setval('public.clustering_rpc_requests_id_seq', 1, false);


--
-- Name: clustering_sync_version_version_seq; Type: SEQUENCE SET; Schema: public; Owner: kong
--

SELECT pg_catalog.setval('public.clustering_sync_version_version_seq', 1, false);


--
-- Name: acls acls_cache_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.acls
    ADD CONSTRAINT acls_cache_key_key UNIQUE (cache_key);


--
-- Name: acls acls_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.acls
    ADD CONSTRAINT acls_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: acls acls_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.acls
    ADD CONSTRAINT acls_pkey PRIMARY KEY (id);


--
-- Name: acme_storage acme_storage_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.acme_storage
    ADD CONSTRAINT acme_storage_key_key UNIQUE (key);


--
-- Name: acme_storage acme_storage_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.acme_storage
    ADD CONSTRAINT acme_storage_pkey PRIMARY KEY (id);


--
-- Name: admins admins_custom_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.admins
    ADD CONSTRAINT admins_custom_id_key UNIQUE (custom_id);


--
-- Name: admins admins_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.admins
    ADD CONSTRAINT admins_pkey PRIMARY KEY (id);


--
-- Name: admins admins_username_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.admins
    ADD CONSTRAINT admins_username_key UNIQUE (username);


--
-- Name: application_instances application_instances_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.application_instances
    ADD CONSTRAINT application_instances_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: application_instances application_instances_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.application_instances
    ADD CONSTRAINT application_instances_pkey PRIMARY KEY (id);


--
-- Name: application_instances application_instances_ws_id_composite_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.application_instances
    ADD CONSTRAINT application_instances_ws_id_composite_id_unique UNIQUE (ws_id, composite_id);


--
-- Name: applications applications_custom_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_custom_id_key UNIQUE (custom_id);


--
-- Name: applications applications_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: applications applications_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_pkey PRIMARY KEY (id);


--
-- Name: audit_objects audit_objects_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.audit_objects
    ADD CONSTRAINT audit_objects_pkey PRIMARY KEY (id);


--
-- Name: audit_requests audit_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.audit_requests
    ADD CONSTRAINT audit_requests_pkey PRIMARY KEY (request_id);


--
-- Name: basicauth_credentials basicauth_credentials_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.basicauth_credentials
    ADD CONSTRAINT basicauth_credentials_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: basicauth_credentials basicauth_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.basicauth_credentials
    ADD CONSTRAINT basicauth_credentials_pkey PRIMARY KEY (id);


--
-- Name: basicauth_credentials basicauth_credentials_ws_id_username_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.basicauth_credentials
    ADD CONSTRAINT basicauth_credentials_ws_id_username_unique UNIQUE (ws_id, username);


--
-- Name: ca_certificates ca_certificates_cert_digest_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.ca_certificates
    ADD CONSTRAINT ca_certificates_cert_digest_key UNIQUE (cert_digest);


--
-- Name: ca_certificates ca_certificates_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.ca_certificates
    ADD CONSTRAINT ca_certificates_pkey PRIMARY KEY (id);


--
-- Name: certificates certificates_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.certificates
    ADD CONSTRAINT certificates_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: certificates certificates_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.certificates
    ADD CONSTRAINT certificates_pkey PRIMARY KEY (id);


--
-- Name: cluster_events cluster_events_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.cluster_events
    ADD CONSTRAINT cluster_events_pkey PRIMARY KEY (id);


--
-- Name: clustering_data_planes clustering_data_planes_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.clustering_data_planes
    ADD CONSTRAINT clustering_data_planes_pkey PRIMARY KEY (id);


--
-- Name: clustering_rpc_requests clustering_rpc_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.clustering_rpc_requests
    ADD CONSTRAINT clustering_rpc_requests_pkey PRIMARY KEY (id);


--
-- Name: clustering_sync_lock clustering_sync_lock_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.clustering_sync_lock
    ADD CONSTRAINT clustering_sync_lock_pkey PRIMARY KEY (id);


--
-- Name: clustering_sync_version clustering_sync_version_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.clustering_sync_version
    ADD CONSTRAINT clustering_sync_version_pkey PRIMARY KEY (version);


--
-- Name: consumer_group_consumers consumer_group_consumers_cache_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_consumers
    ADD CONSTRAINT consumer_group_consumers_cache_key_key UNIQUE (cache_key);


--
-- Name: consumer_group_consumers consumer_group_consumers_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_consumers
    ADD CONSTRAINT consumer_group_consumers_pkey PRIMARY KEY (consumer_group_id, consumer_id);


--
-- Name: consumer_group_plugins consumer_group_plugins_cache_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_plugins
    ADD CONSTRAINT consumer_group_plugins_cache_key_key UNIQUE (cache_key);


--
-- Name: consumer_group_plugins consumer_group_plugins_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_plugins
    ADD CONSTRAINT consumer_group_plugins_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: consumer_group_plugins consumer_group_plugins_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_plugins
    ADD CONSTRAINT consumer_group_plugins_pkey PRIMARY KEY (id);


--
-- Name: consumer_groups consumer_groups_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_groups
    ADD CONSTRAINT consumer_groups_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: consumer_groups consumer_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_groups
    ADD CONSTRAINT consumer_groups_pkey PRIMARY KEY (id);


--
-- Name: consumer_groups consumer_groups_ws_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_groups
    ADD CONSTRAINT consumer_groups_ws_id_name_unique UNIQUE (ws_id, name);


--
-- Name: consumer_reset_secrets consumer_reset_secrets_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_reset_secrets
    ADD CONSTRAINT consumer_reset_secrets_pkey PRIMARY KEY (id);


--
-- Name: consumers consumers_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumers
    ADD CONSTRAINT consumers_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: consumers consumers_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumers
    ADD CONSTRAINT consumers_pkey PRIMARY KEY (id);


--
-- Name: consumers consumers_ws_id_custom_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumers
    ADD CONSTRAINT consumers_ws_id_custom_id_unique UNIQUE (ws_id, custom_id);


--
-- Name: consumers consumers_ws_id_username_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumers
    ADD CONSTRAINT consumers_ws_id_username_unique UNIQUE (ws_id, username);


--
-- Name: credentials credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.credentials
    ADD CONSTRAINT credentials_pkey PRIMARY KEY (id);


--
-- Name: custom_plugins custom_plugins_id_ws_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.custom_plugins
    ADD CONSTRAINT custom_plugins_id_ws_id_key UNIQUE (id, ws_id);


--
-- Name: custom_plugins custom_plugins_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.custom_plugins
    ADD CONSTRAINT custom_plugins_name_key UNIQUE (name);


--
-- Name: custom_plugins custom_plugins_name_ws_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.custom_plugins
    ADD CONSTRAINT custom_plugins_name_ws_id_key UNIQUE (name, ws_id);


--
-- Name: custom_plugins custom_plugins_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.custom_plugins
    ADD CONSTRAINT custom_plugins_pkey PRIMARY KEY (id);


--
-- Name: degraphql_routes degraphql_routes_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.degraphql_routes
    ADD CONSTRAINT degraphql_routes_pkey PRIMARY KEY (id);


--
-- Name: developers developers_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.developers
    ADD CONSTRAINT developers_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: developers developers_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.developers
    ADD CONSTRAINT developers_pkey PRIMARY KEY (id);


--
-- Name: developers developers_ws_id_custom_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.developers
    ADD CONSTRAINT developers_ws_id_custom_id_unique UNIQUE (ws_id, custom_id);


--
-- Name: developers developers_ws_id_email_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.developers
    ADD CONSTRAINT developers_ws_id_email_unique UNIQUE (ws_id, email);


--
-- Name: document_objects document_objects_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.document_objects
    ADD CONSTRAINT document_objects_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: document_objects document_objects_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.document_objects
    ADD CONSTRAINT document_objects_pkey PRIMARY KEY (id);


--
-- Name: document_objects document_objects_ws_id_path_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.document_objects
    ADD CONSTRAINT document_objects_ws_id_path_unique UNIQUE (ws_id, path);


--
-- Name: event_hooks event_hooks_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.event_hooks
    ADD CONSTRAINT event_hooks_id_key UNIQUE (id);


--
-- Name: files files_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: files files_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_pkey PRIMARY KEY (id);


--
-- Name: files files_ws_id_path_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_ws_id_path_unique UNIQUE (ws_id, path);


--
-- Name: filter_chains filter_chains_cache_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.filter_chains
    ADD CONSTRAINT filter_chains_cache_key_key UNIQUE (cache_key);


--
-- Name: filter_chains filter_chains_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.filter_chains
    ADD CONSTRAINT filter_chains_name_key UNIQUE (name);


--
-- Name: filter_chains filter_chains_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.filter_chains
    ADD CONSTRAINT filter_chains_pkey PRIMARY KEY (id);


--
-- Name: graphql_ratelimiting_advanced_cost_decoration graphql_ratelimiting_advanced_cost_decoration_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.graphql_ratelimiting_advanced_cost_decoration
    ADD CONSTRAINT graphql_ratelimiting_advanced_cost_decoration_pkey PRIMARY KEY (id);


--
-- Name: group_rbac_roles group_rbac_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.group_rbac_roles
    ADD CONSTRAINT group_rbac_roles_pkey PRIMARY KEY (group_id, rbac_role_id);


--
-- Name: groups groups_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_name_key UNIQUE (name);


--
-- Name: groups groups_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.groups
    ADD CONSTRAINT groups_pkey PRIMARY KEY (id);


--
-- Name: header_cert_auth_credentials header_cert_auth_credentials_cache_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.header_cert_auth_credentials
    ADD CONSTRAINT header_cert_auth_credentials_cache_key_key UNIQUE (cache_key);


--
-- Name: header_cert_auth_credentials header_cert_auth_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.header_cert_auth_credentials
    ADD CONSTRAINT header_cert_auth_credentials_pkey PRIMARY KEY (id);


--
-- Name: hmacauth_credentials hmacauth_credentials_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.hmacauth_credentials
    ADD CONSTRAINT hmacauth_credentials_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: hmacauth_credentials hmacauth_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.hmacauth_credentials
    ADD CONSTRAINT hmacauth_credentials_pkey PRIMARY KEY (id);


--
-- Name: hmacauth_credentials hmacauth_credentials_ws_id_username_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.hmacauth_credentials
    ADD CONSTRAINT hmacauth_credentials_ws_id_username_unique UNIQUE (ws_id, username);


--
-- Name: jwt_secrets jwt_secrets_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.jwt_secrets
    ADD CONSTRAINT jwt_secrets_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: jwt_secrets jwt_secrets_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.jwt_secrets
    ADD CONSTRAINT jwt_secrets_pkey PRIMARY KEY (id);


--
-- Name: jwt_secrets jwt_secrets_ws_id_key_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.jwt_secrets
    ADD CONSTRAINT jwt_secrets_ws_id_key_unique UNIQUE (ws_id, key);


--
-- Name: jwt_signer_jwks jwt_signer_jwks_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.jwt_signer_jwks
    ADD CONSTRAINT jwt_signer_jwks_name_key UNIQUE (name);


--
-- Name: jwt_signer_jwks jwt_signer_jwks_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.jwt_signer_jwks
    ADD CONSTRAINT jwt_signer_jwks_pkey PRIMARY KEY (id);


--
-- Name: key_sets key_sets_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.key_sets
    ADD CONSTRAINT key_sets_name_key UNIQUE (name);


--
-- Name: key_sets key_sets_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.key_sets
    ADD CONSTRAINT key_sets_pkey PRIMARY KEY (id);


--
-- Name: keyauth_credentials keyauth_credentials_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_credentials
    ADD CONSTRAINT keyauth_credentials_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: keyauth_credentials keyauth_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_credentials
    ADD CONSTRAINT keyauth_credentials_pkey PRIMARY KEY (id);


--
-- Name: keyauth_credentials keyauth_credentials_ws_id_key_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_credentials
    ADD CONSTRAINT keyauth_credentials_ws_id_key_unique UNIQUE (ws_id, key);


--
-- Name: keyauth_enc_credentials keyauth_enc_credentials_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_enc_credentials
    ADD CONSTRAINT keyauth_enc_credentials_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: keyauth_enc_credentials keyauth_enc_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_enc_credentials
    ADD CONSTRAINT keyauth_enc_credentials_pkey PRIMARY KEY (id);


--
-- Name: keyauth_enc_credentials keyauth_enc_credentials_ws_id_key_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_enc_credentials
    ADD CONSTRAINT keyauth_enc_credentials_ws_id_key_unique UNIQUE (ws_id, key);


--
-- Name: keyring_keys keyring_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyring_keys
    ADD CONSTRAINT keyring_keys_pkey PRIMARY KEY (id);


--
-- Name: keyring_meta keyring_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyring_meta
    ADD CONSTRAINT keyring_meta_pkey PRIMARY KEY (id);


--
-- Name: keys keys_cache_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keys
    ADD CONSTRAINT keys_cache_key_key UNIQUE (cache_key);


--
-- Name: keys keys_kid_set_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keys
    ADD CONSTRAINT keys_kid_set_id_key UNIQUE (kid, set_id);


--
-- Name: keys keys_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keys
    ADD CONSTRAINT keys_name_key UNIQUE (name);


--
-- Name: keys keys_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keys
    ADD CONSTRAINT keys_pkey PRIMARY KEY (id);


--
-- Name: keys keys_x5t_set_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keys
    ADD CONSTRAINT keys_x5t_set_id_unique UNIQUE (x5t, set_id);


--
-- Name: konnect_applications konnect_applications_client_id_ws_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.konnect_applications
    ADD CONSTRAINT konnect_applications_client_id_ws_id_key UNIQUE (client_id, ws_id);


--
-- Name: konnect_applications konnect_applications_id_ws_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.konnect_applications
    ADD CONSTRAINT konnect_applications_id_ws_id_key UNIQUE (id, ws_id);


--
-- Name: konnect_applications konnect_applications_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.konnect_applications
    ADD CONSTRAINT konnect_applications_pkey PRIMARY KEY (id);


--
-- Name: legacy_files legacy_files_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.legacy_files
    ADD CONSTRAINT legacy_files_name_key UNIQUE (name);


--
-- Name: legacy_files legacy_files_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.legacy_files
    ADD CONSTRAINT legacy_files_pkey PRIMARY KEY (id);


--
-- Name: license_llm_data license_llm_data_model_name_year_week_of_year_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.license_llm_data
    ADD CONSTRAINT license_llm_data_model_name_year_week_of_year_key UNIQUE (model_name, year, week_of_year);


--
-- Name: licenses licenses_checksum_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.licenses
    ADD CONSTRAINT licenses_checksum_key UNIQUE (checksum);


--
-- Name: licenses licenses_payload_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.licenses
    ADD CONSTRAINT licenses_payload_key UNIQUE (payload);


--
-- Name: licenses licenses_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.licenses
    ADD CONSTRAINT licenses_pkey PRIMARY KEY (id);


--
-- Name: locks locks_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.locks
    ADD CONSTRAINT locks_pkey PRIMARY KEY (key);


--
-- Name: login_attempts login_attempts_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.login_attempts
    ADD CONSTRAINT login_attempts_pkey PRIMARY KEY (consumer_id, attempt_type);


--
-- Name: mtls_auth_credentials mtls_auth_credentials_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.mtls_auth_credentials
    ADD CONSTRAINT mtls_auth_credentials_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: mtls_auth_credentials mtls_auth_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.mtls_auth_credentials
    ADD CONSTRAINT mtls_auth_credentials_pkey PRIMARY KEY (id);


--
-- Name: mtls_auth_credentials mtls_auth_credentials_ws_id_cache_key_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.mtls_auth_credentials
    ADD CONSTRAINT mtls_auth_credentials_ws_id_cache_key_unique UNIQUE (ws_id, cache_key);


--
-- Name: oauth2_authorization_codes oauth2_authorization_codes_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_authorization_codes
    ADD CONSTRAINT oauth2_authorization_codes_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: oauth2_authorization_codes oauth2_authorization_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_authorization_codes
    ADD CONSTRAINT oauth2_authorization_codes_pkey PRIMARY KEY (id);


--
-- Name: oauth2_authorization_codes oauth2_authorization_codes_ws_id_code_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_authorization_codes
    ADD CONSTRAINT oauth2_authorization_codes_ws_id_code_unique UNIQUE (ws_id, code);


--
-- Name: oauth2_credentials oauth2_credentials_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_credentials
    ADD CONSTRAINT oauth2_credentials_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: oauth2_credentials oauth2_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_credentials
    ADD CONSTRAINT oauth2_credentials_pkey PRIMARY KEY (id);


--
-- Name: oauth2_credentials oauth2_credentials_ws_id_client_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_credentials
    ADD CONSTRAINT oauth2_credentials_ws_id_client_id_unique UNIQUE (ws_id, client_id);


--
-- Name: oauth2_tokens oauth2_tokens_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_tokens
    ADD CONSTRAINT oauth2_tokens_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: oauth2_tokens oauth2_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_tokens
    ADD CONSTRAINT oauth2_tokens_pkey PRIMARY KEY (id);


--
-- Name: oauth2_tokens oauth2_tokens_ws_id_access_token_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_tokens
    ADD CONSTRAINT oauth2_tokens_ws_id_access_token_unique UNIQUE (ws_id, access_token);


--
-- Name: oauth2_tokens oauth2_tokens_ws_id_refresh_token_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_tokens
    ADD CONSTRAINT oauth2_tokens_ws_id_refresh_token_unique UNIQUE (ws_id, refresh_token);


--
-- Name: oic_issuers oic_issuers_issuer_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oic_issuers
    ADD CONSTRAINT oic_issuers_issuer_key UNIQUE (issuer);


--
-- Name: oic_issuers oic_issuers_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oic_issuers
    ADD CONSTRAINT oic_issuers_pkey PRIMARY KEY (id);


--
-- Name: oic_jwks oic_jwks_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oic_jwks
    ADD CONSTRAINT oic_jwks_pkey PRIMARY KEY (id);


--
-- Name: parameters parameters_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.parameters
    ADD CONSTRAINT parameters_pkey PRIMARY KEY (key);


--
-- Name: partials partials_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.partials
    ADD CONSTRAINT partials_id_key UNIQUE (id);


--
-- Name: plugins plugins_cache_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_cache_key_key UNIQUE (cache_key);


--
-- Name: plugins plugins_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: plugins_partials plugins_partials_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins_partials
    ADD CONSTRAINT plugins_partials_id_key UNIQUE (id);


--
-- Name: plugins plugins_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_pkey PRIMARY KEY (id);


--
-- Name: plugins plugins_ws_id_instance_name_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_ws_id_instance_name_unique UNIQUE (ws_id, instance_name);


--
-- Name: ratelimiting_metrics ratelimiting_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.ratelimiting_metrics
    ADD CONSTRAINT ratelimiting_metrics_pkey PRIMARY KEY (identifier, period, period_date, service_id, route_id);


--
-- Name: rbac_role_endpoints rbac_role_endpoints_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_role_endpoints
    ADD CONSTRAINT rbac_role_endpoints_pkey PRIMARY KEY (role_id, workspace, endpoint);


--
-- Name: rbac_role_entities rbac_role_entities_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_role_entities
    ADD CONSTRAINT rbac_role_entities_pkey PRIMARY KEY (role_id, entity_id);


--
-- Name: rbac_roles rbac_roles_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_roles
    ADD CONSTRAINT rbac_roles_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: rbac_roles rbac_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_roles
    ADD CONSTRAINT rbac_roles_pkey PRIMARY KEY (id);


--
-- Name: rbac_roles rbac_roles_ws_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_roles
    ADD CONSTRAINT rbac_roles_ws_id_name_unique UNIQUE (ws_id, name);


--
-- Name: rbac_user_groups rbac_user_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_user_groups
    ADD CONSTRAINT rbac_user_groups_pkey PRIMARY KEY (user_id, group_id);


--
-- Name: rbac_user_roles rbac_user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_user_roles
    ADD CONSTRAINT rbac_user_roles_pkey PRIMARY KEY (user_id, role_id);


--
-- Name: rbac_users rbac_users_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_users
    ADD CONSTRAINT rbac_users_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: rbac_users rbac_users_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_users
    ADD CONSTRAINT rbac_users_pkey PRIMARY KEY (id);


--
-- Name: rbac_users rbac_users_user_token_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_users
    ADD CONSTRAINT rbac_users_user_token_key UNIQUE (user_token);


--
-- Name: rbac_users rbac_users_ws_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_users
    ADD CONSTRAINT rbac_users_ws_id_name_unique UNIQUE (ws_id, name);


--
-- Name: response_ratelimiting_metrics response_ratelimiting_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.response_ratelimiting_metrics
    ADD CONSTRAINT response_ratelimiting_metrics_pkey PRIMARY KEY (identifier, period, period_date, service_id, route_id);


--
-- Name: rl_counters rl_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rl_counters
    ADD CONSTRAINT rl_counters_pkey PRIMARY KEY (key, namespace, window_start, window_size);


--
-- Name: routes routes_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: routes routes_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_pkey PRIMARY KEY (id);


--
-- Name: routes routes_ws_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_ws_id_name_unique UNIQUE (ws_id, name);


--
-- Name: schema_meta schema_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.schema_meta
    ADD CONSTRAINT schema_meta_pkey PRIMARY KEY (key, subsystem);


--
-- Name: services services_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: services services_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_pkey PRIMARY KEY (id);


--
-- Name: services services_ws_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_ws_id_name_unique UNIQUE (ws_id, name);


--
-- Name: session_metadatas session_metadatas_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.session_metadatas
    ADD CONSTRAINT session_metadatas_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_session_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_session_id_key UNIQUE (session_id);


--
-- Name: sm_vaults sm_vaults_id_ws_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.sm_vaults
    ADD CONSTRAINT sm_vaults_id_ws_id_key UNIQUE (id, ws_id);


--
-- Name: sm_vaults sm_vaults_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.sm_vaults
    ADD CONSTRAINT sm_vaults_pkey PRIMARY KEY (id);


--
-- Name: sm_vaults sm_vaults_prefix_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.sm_vaults
    ADD CONSTRAINT sm_vaults_prefix_key UNIQUE (prefix);


--
-- Name: sm_vaults sm_vaults_prefix_ws_id_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.sm_vaults
    ADD CONSTRAINT sm_vaults_prefix_ws_id_key UNIQUE (prefix, ws_id);


--
-- Name: snis snis_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.snis
    ADD CONSTRAINT snis_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: snis snis_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.snis
    ADD CONSTRAINT snis_name_key UNIQUE (name);


--
-- Name: snis snis_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.snis
    ADD CONSTRAINT snis_pkey PRIMARY KEY (id);


--
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (entity_id);


--
-- Name: targets targets_cache_key_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.targets
    ADD CONSTRAINT targets_cache_key_key UNIQUE (cache_key);


--
-- Name: targets targets_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.targets
    ADD CONSTRAINT targets_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: targets targets_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.targets
    ADD CONSTRAINT targets_pkey PRIMARY KEY (id);


--
-- Name: upstreams upstreams_id_ws_id_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.upstreams
    ADD CONSTRAINT upstreams_id_ws_id_unique UNIQUE (id, ws_id);


--
-- Name: upstreams upstreams_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.upstreams
    ADD CONSTRAINT upstreams_pkey PRIMARY KEY (id);


--
-- Name: upstreams upstreams_ws_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.upstreams
    ADD CONSTRAINT upstreams_ws_id_name_unique UNIQUE (ws_id, name);


--
-- Name: vault_auth_vaults vault_auth_vaults_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vault_auth_vaults
    ADD CONSTRAINT vault_auth_vaults_name_key UNIQUE (name);


--
-- Name: vault_auth_vaults vault_auth_vaults_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vault_auth_vaults
    ADD CONSTRAINT vault_auth_vaults_pkey PRIMARY KEY (id);


--
-- Name: vaults vaults_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vaults
    ADD CONSTRAINT vaults_name_key UNIQUE (name);


--
-- Name: vaults vaults_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vaults
    ADD CONSTRAINT vaults_pkey PRIMARY KEY (id);


--
-- Name: vitals_code_classes_by_cluster vitals_code_classes_by_cluster_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_code_classes_by_cluster
    ADD CONSTRAINT vitals_code_classes_by_cluster_pkey PRIMARY KEY (code_class, duration, at);


--
-- Name: vitals_code_classes_by_workspace vitals_code_classes_by_workspace_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_code_classes_by_workspace
    ADD CONSTRAINT vitals_code_classes_by_workspace_pkey PRIMARY KEY (workspace_id, code_class, duration, at);


--
-- Name: vitals_codes_by_consumer_route vitals_codes_by_consumer_route_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_codes_by_consumer_route
    ADD CONSTRAINT vitals_codes_by_consumer_route_pkey PRIMARY KEY (consumer_id, route_id, code, duration, at);


--
-- Name: vitals_codes_by_route vitals_codes_by_route_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_codes_by_route
    ADD CONSTRAINT vitals_codes_by_route_pkey PRIMARY KEY (route_id, code, duration, at);


--
-- Name: vitals_locks vitals_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_locks
    ADD CONSTRAINT vitals_locks_pkey PRIMARY KEY (key);


--
-- Name: vitals_node_meta vitals_node_meta_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_node_meta
    ADD CONSTRAINT vitals_node_meta_pkey PRIMARY KEY (node_id);


--
-- Name: vitals_stats_days vitals_stats_days_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_stats_days
    ADD CONSTRAINT vitals_stats_days_pkey PRIMARY KEY (node_id, at);


--
-- Name: vitals_stats_hours vitals_stats_hours_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_stats_hours
    ADD CONSTRAINT vitals_stats_hours_pkey PRIMARY KEY (at);


--
-- Name: vitals_stats_minutes vitals_stats_minutes_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_stats_minutes
    ADD CONSTRAINT vitals_stats_minutes_pkey PRIMARY KEY (node_id, at);


--
-- Name: vitals_stats_seconds vitals_stats_seconds_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.vitals_stats_seconds
    ADD CONSTRAINT vitals_stats_seconds_pkey PRIMARY KEY (node_id, at);


--
-- Name: workspace_entities workspace_entities_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.workspace_entities
    ADD CONSTRAINT workspace_entities_pkey PRIMARY KEY (workspace_id, entity_id, unique_field_name);


--
-- Name: workspace_entity_counters workspace_entity_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.workspace_entity_counters
    ADD CONSTRAINT workspace_entity_counters_pkey PRIMARY KEY (workspace_id, entity_type);


--
-- Name: workspaces workspaces_name_key; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.workspaces
    ADD CONSTRAINT workspaces_name_key UNIQUE (name);


--
-- Name: workspaces workspaces_pkey; Type: CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.workspaces
    ADD CONSTRAINT workspaces_pkey PRIMARY KEY (id);


--
-- Name: acls_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX acls_consumer_id_idx ON public.acls USING btree (consumer_id);


--
-- Name: acls_group_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX acls_group_idx ON public.acls USING btree ("group");


--
-- Name: acls_tags_idex_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX acls_tags_idex_tags_idx ON public.acls USING gin (tags);


--
-- Name: acme_storage_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX acme_storage_ttl_idx ON public.acme_storage USING btree (ttl);


--
-- Name: applications_developer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX applications_developer_id_idx ON public.applications USING btree (developer_id);


--
-- Name: audit_objects_request_timestamp_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX audit_objects_request_timestamp_idx ON public.audit_objects USING btree (request_timestamp);


--
-- Name: audit_objects_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX audit_objects_ttl_idx ON public.audit_objects USING btree (ttl);


--
-- Name: audit_requests_request_timestamp_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX audit_requests_request_timestamp_idx ON public.audit_requests USING btree (request_timestamp);


--
-- Name: audit_requests_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX audit_requests_ttl_idx ON public.audit_requests USING btree (ttl);


--
-- Name: basicauth_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX basicauth_consumer_id_idx ON public.basicauth_credentials USING btree (consumer_id);


--
-- Name: basicauth_tags_idex_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX basicauth_tags_idex_tags_idx ON public.basicauth_credentials USING gin (tags);


--
-- Name: certificates_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX certificates_tags_idx ON public.certificates USING gin (tags);


--
-- Name: cluster_events_at_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX cluster_events_at_idx ON public.cluster_events USING btree (at);


--
-- Name: cluster_events_channel_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX cluster_events_channel_idx ON public.cluster_events USING btree (channel);


--
-- Name: cluster_events_expire_at_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX cluster_events_expire_at_idx ON public.cluster_events USING btree (expire_at);


--
-- Name: clustering_data_planes_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX clustering_data_planes_ttl_idx ON public.clustering_data_planes USING btree (ttl);


--
-- Name: clustering_rpc_requests_node_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX clustering_rpc_requests_node_id_idx ON public.clustering_rpc_requests USING btree (node_id);


--
-- Name: clustering_sync_delta_version_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX clustering_sync_delta_version_idx ON public.clustering_sync_delta USING btree (version);


--
-- Name: consumer_group_consumers_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumer_group_consumers_consumer_id_idx ON public.consumer_group_consumers USING btree (consumer_id);


--
-- Name: consumer_group_consumers_group_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumer_group_consumers_group_id_idx ON public.consumer_group_consumers USING btree (consumer_group_id);


--
-- Name: consumer_group_plugins_group_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumer_group_plugins_group_id_idx ON public.consumer_group_plugins USING btree (consumer_group_id);


--
-- Name: consumer_group_plugins_plugin_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumer_group_plugins_plugin_name_idx ON public.consumer_group_plugins USING btree (name);


--
-- Name: consumer_groups_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumer_groups_name_idx ON public.consumer_groups USING btree (name);


--
-- Name: consumer_groups_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumer_groups_tags_idx ON public.consumer_groups USING gin (tags);


--
-- Name: consumer_reset_secrets_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumer_reset_secrets_consumer_id_idx ON public.consumer_reset_secrets USING btree (consumer_id);


--
-- Name: consumers_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumers_tags_idx ON public.consumers USING gin (tags);


--
-- Name: consumers_type_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumers_type_idx ON public.consumers USING btree (type);


--
-- Name: consumers_username_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX consumers_username_idx ON public.consumers USING btree (lower(username));


--
-- Name: credentials_consumer_id_plugin; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX credentials_consumer_id_plugin ON public.credentials USING btree (consumer_id, plugin);


--
-- Name: credentials_consumer_type; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX credentials_consumer_type ON public.credentials USING btree (consumer_id);


--
-- Name: custom_plugins_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX custom_plugins_tags_idx ON public.custom_plugins USING gin (tags);


--
-- Name: degraphql_routes_fkey_service; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX degraphql_routes_fkey_service ON public.degraphql_routes USING btree (service_id);


--
-- Name: developers_rbac_user_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX developers_rbac_user_id_idx ON public.developers USING btree (rbac_user_id);


--
-- Name: files_path_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX files_path_idx ON public.files USING btree (path);


--
-- Name: filter_chains_cache_key_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE UNIQUE INDEX filter_chains_cache_key_idx ON public.filter_chains USING btree (cache_key);


--
-- Name: filter_chains_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE UNIQUE INDEX filter_chains_name_idx ON public.filter_chains USING btree (name);


--
-- Name: filter_chains_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX filter_chains_tags_idx ON public.filter_chains USING gin (tags);


--
-- Name: graphql_ratelimiting_advanced_cost_decoration_fkey_service; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX graphql_ratelimiting_advanced_cost_decoration_fkey_service ON public.graphql_ratelimiting_advanced_cost_decoration USING btree (service_id);


--
-- Name: groups_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX groups_name_idx ON public.groups USING btree (name);


--
-- Name: header_cert_auth_common_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX header_cert_auth_common_name_idx ON public.header_cert_auth_credentials USING btree (subject_name);


--
-- Name: header_cert_auth_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX header_cert_auth_consumer_id_idx ON public.header_cert_auth_credentials USING btree (consumer_id);


--
-- Name: header_cert_auth_credentials_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX header_cert_auth_credentials_tags_idx ON public.header_cert_auth_credentials USING gin (tags);


--
-- Name: hmacauth_credentials_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX hmacauth_credentials_consumer_id_idx ON public.hmacauth_credentials USING btree (consumer_id);


--
-- Name: hmacauth_tags_idex_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX hmacauth_tags_idex_tags_idx ON public.hmacauth_credentials USING gin (tags);


--
-- Name: jwt_secrets_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX jwt_secrets_consumer_id_idx ON public.jwt_secrets USING btree (consumer_id);


--
-- Name: jwt_secrets_secret_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX jwt_secrets_secret_idx ON public.jwt_secrets USING btree (secret);


--
-- Name: jwtsecrets_tags_idex_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX jwtsecrets_tags_idex_tags_idx ON public.jwt_secrets USING gin (tags);


--
-- Name: key_sets_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX key_sets_tags_idx ON public.key_sets USING gin (tags);


--
-- Name: keyauth_credentials_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX keyauth_credentials_consumer_id_idx ON public.keyauth_credentials USING btree (consumer_id);


--
-- Name: keyauth_credentials_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX keyauth_credentials_ttl_idx ON public.keyauth_credentials USING btree (ttl);


--
-- Name: keyauth_enc_credentials_consum; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX keyauth_enc_credentials_consum ON public.keyauth_enc_credentials USING btree (consumer_id);


--
-- Name: keyauth_enc_credentials_ttl; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX keyauth_enc_credentials_ttl ON public.keyauth_enc_credentials USING btree (ttl);


--
-- Name: keyauth_enc_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX keyauth_enc_tags_idx ON public.keyauth_enc_credentials USING gin (tags);


--
-- Name: keyauth_tags_idex_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX keyauth_tags_idex_tags_idx ON public.keyauth_credentials USING gin (tags);


--
-- Name: keys_fkey_key_sets; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX keys_fkey_key_sets ON public.keys USING btree (set_id);


--
-- Name: keys_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX keys_tags_idx ON public.keys USING gin (tags);


--
-- Name: keys_x5t_with_null_set_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE UNIQUE INDEX keys_x5t_with_null_set_id_idx ON public.keys USING btree (x5t) WHERE (set_id IS NULL);


--
-- Name: konnect_applications_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX konnect_applications_tags_idx ON public.konnect_applications USING gin (tags);


--
-- Name: legacy_files_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX legacy_files_name_idx ON public.legacy_files USING btree (name);


--
-- Name: license_data_key_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE UNIQUE INDEX license_data_key_idx ON public.license_data USING btree (node_id, year, month);


--
-- Name: license_llm_data_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX license_llm_data_idx ON public.license_llm_data USING btree (id, model_name, year, week_of_year);


--
-- Name: locks_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX locks_ttl_idx ON public.locks USING btree (ttl);


--
-- Name: login_attempts_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX login_attempts_ttl_idx ON public.login_attempts USING btree (ttl);


--
-- Name: mtls_auth_common_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX mtls_auth_common_name_idx ON public.mtls_auth_credentials USING btree (subject_name);


--
-- Name: mtls_auth_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX mtls_auth_consumer_id_idx ON public.mtls_auth_credentials USING btree (consumer_id);


--
-- Name: mtls_auth_credentials_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX mtls_auth_credentials_tags_idx ON public.mtls_auth_credentials USING gin (tags);


--
-- Name: oauth2_authorization_codes_authenticated_userid_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_authorization_codes_authenticated_userid_idx ON public.oauth2_authorization_codes USING btree (authenticated_userid);


--
-- Name: oauth2_authorization_codes_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_authorization_codes_ttl_idx ON public.oauth2_authorization_codes USING btree (ttl);


--
-- Name: oauth2_authorization_credential_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_authorization_credential_id_idx ON public.oauth2_authorization_codes USING btree (credential_id);


--
-- Name: oauth2_authorization_service_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_authorization_service_id_idx ON public.oauth2_authorization_codes USING btree (service_id);


--
-- Name: oauth2_credentials_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_credentials_consumer_id_idx ON public.oauth2_credentials USING btree (consumer_id);


--
-- Name: oauth2_credentials_secret_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_credentials_secret_idx ON public.oauth2_credentials USING btree (client_secret);


--
-- Name: oauth2_credentials_tags_idex_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_credentials_tags_idex_tags_idx ON public.oauth2_credentials USING gin (tags);


--
-- Name: oauth2_tokens_authenticated_userid_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_tokens_authenticated_userid_idx ON public.oauth2_tokens USING btree (authenticated_userid);


--
-- Name: oauth2_tokens_credential_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_tokens_credential_id_idx ON public.oauth2_tokens USING btree (credential_id);


--
-- Name: oauth2_tokens_service_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_tokens_service_id_idx ON public.oauth2_tokens USING btree (service_id);


--
-- Name: oauth2_tokens_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX oauth2_tokens_ttl_idx ON public.oauth2_tokens USING btree (ttl);


--
-- Name: partials_name; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX partials_name ON public.partials USING btree (name);


--
-- Name: partials_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX partials_tags_idx ON public.partials USING gin (tags);


--
-- Name: partials_workspace_id; Type: INDEX; Schema: public; Owner: kong
--

CREATE UNIQUE INDEX partials_workspace_id ON public.partials USING btree (ws_id, id);


--
-- Name: partials_workspace_name; Type: INDEX; Schema: public; Owner: kong
--

CREATE UNIQUE INDEX partials_workspace_name ON public.partials USING btree (ws_id, name);


--
-- Name: plugins_consumer_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX plugins_consumer_id_idx ON public.plugins USING btree (consumer_id);


--
-- Name: plugins_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX plugins_name_idx ON public.plugins USING btree (name);


--
-- Name: plugins_partials_link; Type: INDEX; Schema: public; Owner: kong
--

CREATE UNIQUE INDEX plugins_partials_link ON public.plugins_partials USING btree (plugin_id, partial_id, path) WHERE (path IS NOT NULL);


--
-- Name: plugins_route_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX plugins_route_id_idx ON public.plugins USING btree (route_id);


--
-- Name: plugins_service_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX plugins_service_id_idx ON public.plugins USING btree (service_id);


--
-- Name: plugins_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX plugins_tags_idx ON public.plugins USING gin (tags);


--
-- Name: ratelimiting_metrics_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX ratelimiting_metrics_idx ON public.ratelimiting_metrics USING btree (service_id, route_id, period_date, period);


--
-- Name: ratelimiting_metrics_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX ratelimiting_metrics_ttl_idx ON public.ratelimiting_metrics USING btree (ttl);


--
-- Name: rbac_role_default_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX rbac_role_default_idx ON public.rbac_roles USING btree (is_default);


--
-- Name: rbac_role_endpoints_role_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX rbac_role_endpoints_role_idx ON public.rbac_role_endpoints USING btree (role_id);


--
-- Name: rbac_role_entities_role_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX rbac_role_entities_role_idx ON public.rbac_role_entities USING btree (role_id);


--
-- Name: rbac_roles_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX rbac_roles_name_idx ON public.rbac_roles USING btree (name);


--
-- Name: rbac_token_ident_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX rbac_token_ident_idx ON public.rbac_users USING btree (user_token_ident);


--
-- Name: rbac_users_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX rbac_users_name_idx ON public.rbac_users USING btree (name);


--
-- Name: rbac_users_token_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX rbac_users_token_idx ON public.rbac_users USING btree (user_token);


--
-- Name: routes_service_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX routes_service_id_idx ON public.routes USING btree (service_id);


--
-- Name: routes_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX routes_tags_idx ON public.routes USING gin (tags);


--
-- Name: services_fkey_client_certificate; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX services_fkey_client_certificate ON public.services USING btree (client_certificate_id);


--
-- Name: services_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX services_tags_idx ON public.services USING gin (tags);


--
-- Name: session_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX session_id_idx ON public.session_metadatas USING btree (session_id);


--
-- Name: session_sessions_expires_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX session_sessions_expires_idx ON public.sessions USING btree (expires);


--
-- Name: sessions_ttl_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX sessions_ttl_idx ON public.sessions USING btree (ttl);


--
-- Name: sm_vaults_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX sm_vaults_tags_idx ON public.sm_vaults USING gin (tags);


--
-- Name: snis_certificate_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX snis_certificate_id_idx ON public.snis USING btree (certificate_id);


--
-- Name: snis_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX snis_tags_idx ON public.snis USING gin (tags);


--
-- Name: subject_audience_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX subject_audience_idx ON public.session_metadatas USING btree (subject, audience);


--
-- Name: sync_key_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX sync_key_idx ON public.rl_counters USING btree (namespace, window_start);


--
-- Name: tags_entity_name_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX tags_entity_name_idx ON public.tags USING btree (entity_name);


--
-- Name: tags_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX tags_tags_idx ON public.tags USING gin (tags);


--
-- Name: targets_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX targets_tags_idx ON public.targets USING gin (tags);


--
-- Name: targets_target_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX targets_target_idx ON public.targets USING btree (target);


--
-- Name: targets_upstream_id_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX targets_upstream_id_idx ON public.targets USING btree (upstream_id);


--
-- Name: upstreams_fkey_client_certificate; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX upstreams_fkey_client_certificate ON public.upstreams USING btree (client_certificate_id);


--
-- Name: upstreams_tags_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX upstreams_tags_idx ON public.upstreams USING gin (tags);


--
-- Name: vcbr_svc_ts_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX vcbr_svc_ts_idx ON public.vitals_codes_by_route USING btree (service_id, duration, at);


--
-- Name: workspace_entities_composite_idx; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX workspace_entities_composite_idx ON public.workspace_entities USING btree (workspace_id, entity_type, unique_field_name);


--
-- Name: workspace_entities_idx_entity_id; Type: INDEX; Schema: public; Owner: kong
--

CREATE INDEX workspace_entities_idx_entity_id ON public.workspace_entities USING btree (entity_id);


--
-- Name: acls acls_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER acls_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.acls FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: acme_storage acme_storage_ttl_delta_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER acme_storage_ttl_delta_trigger AFTER INSERT ON public.acme_storage FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows_and_gen_deltas('ttl', 'acme_storage', 'false');


--
-- Name: basicauth_credentials basicauth_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER basicauth_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.basicauth_credentials FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: ca_certificates ca_certificates_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER ca_certificates_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.ca_certificates FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: certificates certificates_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER certificates_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.certificates FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: cluster_events cluster_events_ttl_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER cluster_events_ttl_trigger AFTER INSERT ON public.cluster_events FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows('expire_at');


--
-- Name: clustering_data_planes clustering_data_planes_ttl_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER clustering_data_planes_ttl_trigger AFTER INSERT ON public.clustering_data_planes FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows('ttl');


--
-- Name: consumer_groups consumer_groups_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER consumer_groups_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.consumer_groups FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: consumers consumers_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER consumers_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.consumers FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: custom_plugins custom_plugins_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER custom_plugins_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.custom_plugins FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: filter_chains filter_chains_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER filter_chains_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.filter_chains FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: header_cert_auth_credentials header_cert_auth_credentials_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER header_cert_auth_credentials_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.header_cert_auth_credentials FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: hmacauth_credentials hmacauth_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER hmacauth_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.hmacauth_credentials FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: jwt_secrets jwtsecrets_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER jwtsecrets_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.jwt_secrets FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: key_sets key_sets_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER key_sets_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.key_sets FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: keyauth_credentials keyauth_credentials_ttl_delta_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER keyauth_credentials_ttl_delta_trigger AFTER INSERT ON public.keyauth_credentials FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows_and_gen_deltas('ttl', 'keyauth_credentials', 'true');


--
-- Name: keyauth_enc_credentials keyauth_enc_credentials_ttl_delta_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER keyauth_enc_credentials_ttl_delta_trigger AFTER INSERT ON public.keyauth_enc_credentials FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows_and_gen_deltas('ttl', 'keyauth_enc_credentials', 'true');


--
-- Name: keyauth_enc_credentials keyauth_enc_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER keyauth_enc_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.keyauth_enc_credentials FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: keyauth_credentials keyauth_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER keyauth_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.keyauth_credentials FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: keys keys_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER keys_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.keys FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: konnect_applications konnect_applications_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER konnect_applications_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.konnect_applications FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: mtls_auth_credentials mtls_auth_credentials_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER mtls_auth_credentials_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.mtls_auth_credentials FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: oauth2_authorization_codes oauth2_authorization_codes_ttl_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER oauth2_authorization_codes_ttl_trigger AFTER INSERT ON public.oauth2_authorization_codes FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows('ttl');


--
-- Name: oauth2_credentials oauth2_credentials_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER oauth2_credentials_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.oauth2_credentials FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: oauth2_tokens oauth2_tokens_ttl_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER oauth2_tokens_ttl_trigger AFTER INSERT ON public.oauth2_tokens FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows('ttl');


--
-- Name: partials partials_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER partials_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.partials FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: plugins plugins_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER plugins_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.plugins FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: ratelimiting_metrics ratelimiting_metrics_ttl_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER ratelimiting_metrics_ttl_trigger AFTER INSERT ON public.ratelimiting_metrics FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows('ttl');


--
-- Name: routes routes_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER routes_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.routes FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: services services_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER services_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.services FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: sessions sessions_ttl_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER sessions_ttl_trigger AFTER INSERT ON public.sessions FOR EACH STATEMENT EXECUTE FUNCTION public.batch_delete_expired_rows('ttl');


--
-- Name: sm_vaults sm_vaults_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER sm_vaults_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.sm_vaults FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: snis snis_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER snis_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.snis FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: targets targets_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER targets_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.targets FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: upstreams upstreams_sync_tags_trigger; Type: TRIGGER; Schema: public; Owner: kong
--

CREATE TRIGGER upstreams_sync_tags_trigger AFTER INSERT OR DELETE OR UPDATE OF tags ON public.upstreams FOR EACH ROW EXECUTE FUNCTION public.sync_tags();


--
-- Name: acls acls_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.acls
    ADD CONSTRAINT acls_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id) ON DELETE CASCADE;


--
-- Name: acls acls_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.acls
    ADD CONSTRAINT acls_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: admins admins_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.admins
    ADD CONSTRAINT admins_consumer_id_fkey FOREIGN KEY (consumer_id) REFERENCES public.consumers(id);


--
-- Name: admins admins_rbac_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.admins
    ADD CONSTRAINT admins_rbac_user_id_fkey FOREIGN KEY (rbac_user_id) REFERENCES public.rbac_users(id);


--
-- Name: application_instances application_instances_application_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.application_instances
    ADD CONSTRAINT application_instances_application_id_fkey FOREIGN KEY (application_id, ws_id) REFERENCES public.applications(id, ws_id);


--
-- Name: application_instances application_instances_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.application_instances
    ADD CONSTRAINT application_instances_service_id_fkey FOREIGN KEY (service_id, ws_id) REFERENCES public.services(id, ws_id);


--
-- Name: application_instances application_instances_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.application_instances
    ADD CONSTRAINT application_instances_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: applications applications_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id);


--
-- Name: applications applications_developer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_developer_id_fkey FOREIGN KEY (developer_id, ws_id) REFERENCES public.developers(id, ws_id);


--
-- Name: applications applications_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.applications
    ADD CONSTRAINT applications_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: basicauth_credentials basicauth_credentials_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.basicauth_credentials
    ADD CONSTRAINT basicauth_credentials_consumer_id_fkey FOREIGN KEY (consumer_id) REFERENCES public.consumers(id) ON DELETE CASCADE;


--
-- Name: basicauth_credentials basicauth_credentials_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.basicauth_credentials
    ADD CONSTRAINT basicauth_credentials_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: certificates certificates_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.certificates
    ADD CONSTRAINT certificates_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: clustering_sync_delta clustering_sync_delta_version_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.clustering_sync_delta
    ADD CONSTRAINT clustering_sync_delta_version_fkey FOREIGN KEY (version) REFERENCES public.clustering_sync_version(version) ON DELETE CASCADE;


--
-- Name: consumer_group_consumers consumer_group_consumers_consumer_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_consumers
    ADD CONSTRAINT consumer_group_consumers_consumer_group_id_fkey FOREIGN KEY (consumer_group_id) REFERENCES public.consumer_groups(id) ON DELETE CASCADE;


--
-- Name: consumer_group_consumers consumer_group_consumers_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_consumers
    ADD CONSTRAINT consumer_group_consumers_consumer_id_fkey FOREIGN KEY (consumer_id) REFERENCES public.consumers(id) ON DELETE CASCADE;


--
-- Name: consumer_group_plugins consumer_group_plugins_consumer_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_plugins
    ADD CONSTRAINT consumer_group_plugins_consumer_group_id_fkey FOREIGN KEY (consumer_group_id, ws_id) REFERENCES public.consumer_groups(id, ws_id) ON DELETE CASCADE;


--
-- Name: consumer_group_plugins consumer_group_plugins_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_group_plugins
    ADD CONSTRAINT consumer_group_plugins_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: consumer_groups consumer_groups_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_groups
    ADD CONSTRAINT consumer_groups_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: consumer_reset_secrets consumer_reset_secrets_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumer_reset_secrets
    ADD CONSTRAINT consumer_reset_secrets_consumer_id_fkey FOREIGN KEY (consumer_id) REFERENCES public.consumers(id) ON DELETE CASCADE;


--
-- Name: consumers consumers_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.consumers
    ADD CONSTRAINT consumers_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: credentials credentials_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.credentials
    ADD CONSTRAINT credentials_consumer_id_fkey FOREIGN KEY (consumer_id) REFERENCES public.consumers(id) ON DELETE CASCADE;


--
-- Name: custom_plugins custom_plugins_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.custom_plugins
    ADD CONSTRAINT custom_plugins_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id);


--
-- Name: degraphql_routes degraphql_routes_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.degraphql_routes
    ADD CONSTRAINT degraphql_routes_service_id_fkey FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE CASCADE;


--
-- Name: developers developers_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.developers
    ADD CONSTRAINT developers_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id);


--
-- Name: developers developers_rbac_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.developers
    ADD CONSTRAINT developers_rbac_user_id_fkey FOREIGN KEY (rbac_user_id, ws_id) REFERENCES public.rbac_users(id, ws_id);


--
-- Name: developers developers_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.developers
    ADD CONSTRAINT developers_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: document_objects document_objects_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.document_objects
    ADD CONSTRAINT document_objects_service_id_fkey FOREIGN KEY (service_id, ws_id) REFERENCES public.services(id, ws_id);


--
-- Name: document_objects document_objects_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.document_objects
    ADD CONSTRAINT document_objects_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: files files_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.files
    ADD CONSTRAINT files_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: filter_chains filter_chains_route_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.filter_chains
    ADD CONSTRAINT filter_chains_route_id_fkey FOREIGN KEY (route_id) REFERENCES public.routes(id) ON DELETE CASCADE;


--
-- Name: filter_chains filter_chains_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.filter_chains
    ADD CONSTRAINT filter_chains_service_id_fkey FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE CASCADE;


--
-- Name: filter_chains filter_chains_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.filter_chains
    ADD CONSTRAINT filter_chains_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: graphql_ratelimiting_advanced_cost_decoration graphql_ratelimiting_advanced_cost_decoration_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.graphql_ratelimiting_advanced_cost_decoration
    ADD CONSTRAINT graphql_ratelimiting_advanced_cost_decoration_service_id_fkey FOREIGN KEY (service_id) REFERENCES public.services(id) ON DELETE CASCADE;


--
-- Name: group_rbac_roles group_rbac_roles_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.group_rbac_roles
    ADD CONSTRAINT group_rbac_roles_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: group_rbac_roles group_rbac_roles_rbac_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.group_rbac_roles
    ADD CONSTRAINT group_rbac_roles_rbac_role_id_fkey FOREIGN KEY (rbac_role_id) REFERENCES public.rbac_roles(id) ON DELETE CASCADE;


--
-- Name: group_rbac_roles group_rbac_roles_workspace_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.group_rbac_roles
    ADD CONSTRAINT group_rbac_roles_workspace_id_fkey FOREIGN KEY (workspace_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: header_cert_auth_credentials header_cert_auth_credentials_ca_certificate_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.header_cert_auth_credentials
    ADD CONSTRAINT header_cert_auth_credentials_ca_certificate_id_fkey FOREIGN KEY (ca_certificate_id) REFERENCES public.ca_certificates(id) ON DELETE CASCADE;


--
-- Name: header_cert_auth_credentials header_cert_auth_credentials_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.header_cert_auth_credentials
    ADD CONSTRAINT header_cert_auth_credentials_consumer_id_fkey FOREIGN KEY (consumer_id) REFERENCES public.consumers(id) ON DELETE CASCADE;


--
-- Name: header_cert_auth_credentials header_cert_auth_credentials_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.header_cert_auth_credentials
    ADD CONSTRAINT header_cert_auth_credentials_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id);


--
-- Name: hmacauth_credentials hmacauth_credentials_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.hmacauth_credentials
    ADD CONSTRAINT hmacauth_credentials_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id) ON DELETE CASCADE;


--
-- Name: hmacauth_credentials hmacauth_credentials_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.hmacauth_credentials
    ADD CONSTRAINT hmacauth_credentials_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: jwt_secrets jwt_secrets_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.jwt_secrets
    ADD CONSTRAINT jwt_secrets_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id) ON DELETE CASCADE;


--
-- Name: jwt_secrets jwt_secrets_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.jwt_secrets
    ADD CONSTRAINT jwt_secrets_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: key_sets key_sets_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.key_sets
    ADD CONSTRAINT key_sets_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: keyauth_credentials keyauth_credentials_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_credentials
    ADD CONSTRAINT keyauth_credentials_consumer_id_fkey FOREIGN KEY (consumer_id) REFERENCES public.consumers(id) ON DELETE CASCADE;


--
-- Name: keyauth_credentials keyauth_credentials_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_credentials
    ADD CONSTRAINT keyauth_credentials_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: keyauth_enc_credentials keyauth_enc_credentials_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_enc_credentials
    ADD CONSTRAINT keyauth_enc_credentials_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id) ON DELETE CASCADE;


--
-- Name: keyauth_enc_credentials keyauth_enc_credentials_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keyauth_enc_credentials
    ADD CONSTRAINT keyauth_enc_credentials_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id);


--
-- Name: keys keys_set_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keys
    ADD CONSTRAINT keys_set_id_fkey FOREIGN KEY (set_id) REFERENCES public.key_sets(id) ON DELETE CASCADE;


--
-- Name: keys keys_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.keys
    ADD CONSTRAINT keys_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: konnect_applications konnect_applications_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.konnect_applications
    ADD CONSTRAINT konnect_applications_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id);


--
-- Name: login_attempts login_attempts_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.login_attempts
    ADD CONSTRAINT login_attempts_consumer_id_fkey FOREIGN KEY (consumer_id) REFERENCES public.consumers(id) ON DELETE CASCADE;


--
-- Name: mtls_auth_credentials mtls_auth_credentials_ca_certificate_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.mtls_auth_credentials
    ADD CONSTRAINT mtls_auth_credentials_ca_certificate_id_fkey FOREIGN KEY (ca_certificate_id) REFERENCES public.ca_certificates(id) ON DELETE CASCADE;


--
-- Name: mtls_auth_credentials mtls_auth_credentials_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.mtls_auth_credentials
    ADD CONSTRAINT mtls_auth_credentials_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id) ON DELETE CASCADE;


--
-- Name: mtls_auth_credentials mtls_auth_credentials_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.mtls_auth_credentials
    ADD CONSTRAINT mtls_auth_credentials_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id);


--
-- Name: oauth2_authorization_codes oauth2_authorization_codes_credential_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_authorization_codes
    ADD CONSTRAINT oauth2_authorization_codes_credential_id_fkey FOREIGN KEY (credential_id, ws_id) REFERENCES public.oauth2_credentials(id, ws_id) ON DELETE CASCADE;


--
-- Name: oauth2_authorization_codes oauth2_authorization_codes_plugin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_authorization_codes
    ADD CONSTRAINT oauth2_authorization_codes_plugin_id_fkey FOREIGN KEY (plugin_id) REFERENCES public.plugins(id) ON DELETE CASCADE;


--
-- Name: oauth2_authorization_codes oauth2_authorization_codes_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_authorization_codes
    ADD CONSTRAINT oauth2_authorization_codes_service_id_fkey FOREIGN KEY (service_id, ws_id) REFERENCES public.services(id, ws_id) ON DELETE CASCADE;


--
-- Name: oauth2_authorization_codes oauth2_authorization_codes_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_authorization_codes
    ADD CONSTRAINT oauth2_authorization_codes_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: oauth2_credentials oauth2_credentials_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_credentials
    ADD CONSTRAINT oauth2_credentials_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id) ON DELETE CASCADE;


--
-- Name: oauth2_credentials oauth2_credentials_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_credentials
    ADD CONSTRAINT oauth2_credentials_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: oauth2_tokens oauth2_tokens_credential_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_tokens
    ADD CONSTRAINT oauth2_tokens_credential_id_fkey FOREIGN KEY (credential_id, ws_id) REFERENCES public.oauth2_credentials(id, ws_id) ON DELETE CASCADE;


--
-- Name: oauth2_tokens oauth2_tokens_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_tokens
    ADD CONSTRAINT oauth2_tokens_service_id_fkey FOREIGN KEY (service_id, ws_id) REFERENCES public.services(id, ws_id) ON DELETE CASCADE;


--
-- Name: oauth2_tokens oauth2_tokens_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.oauth2_tokens
    ADD CONSTRAINT oauth2_tokens_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: partials partials_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.partials
    ADD CONSTRAINT partials_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id);


--
-- Name: plugins plugins_consumer_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_consumer_group_id_fkey FOREIGN KEY (consumer_group_id) REFERENCES public.consumer_groups(id) ON DELETE CASCADE;


--
-- Name: plugins plugins_consumer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_consumer_id_fkey FOREIGN KEY (consumer_id, ws_id) REFERENCES public.consumers(id, ws_id) ON DELETE CASCADE;


--
-- Name: plugins_partials plugins_partials_partial_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins_partials
    ADD CONSTRAINT plugins_partials_partial_id_fkey FOREIGN KEY (partial_id) REFERENCES public.partials(id) ON DELETE CASCADE;


--
-- Name: plugins_partials plugins_partials_plugin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins_partials
    ADD CONSTRAINT plugins_partials_plugin_id_fkey FOREIGN KEY (plugin_id) REFERENCES public.plugins(id) ON DELETE CASCADE;


--
-- Name: plugins plugins_route_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_route_id_fkey FOREIGN KEY (route_id, ws_id) REFERENCES public.routes(id, ws_id) ON DELETE CASCADE;


--
-- Name: plugins plugins_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_service_id_fkey FOREIGN KEY (service_id, ws_id) REFERENCES public.services(id, ws_id) ON DELETE CASCADE;


--
-- Name: plugins plugins_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.plugins
    ADD CONSTRAINT plugins_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: rbac_role_endpoints rbac_role_endpoints_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_role_endpoints
    ADD CONSTRAINT rbac_role_endpoints_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.rbac_roles(id) ON DELETE CASCADE;


--
-- Name: rbac_role_entities rbac_role_entities_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_role_entities
    ADD CONSTRAINT rbac_role_entities_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.rbac_roles(id) ON DELETE CASCADE;


--
-- Name: rbac_roles rbac_roles_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_roles
    ADD CONSTRAINT rbac_roles_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: rbac_user_groups rbac_user_groups_group_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_user_groups
    ADD CONSTRAINT rbac_user_groups_group_id_fkey FOREIGN KEY (group_id) REFERENCES public.groups(id) ON DELETE CASCADE;


--
-- Name: rbac_user_groups rbac_user_groups_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_user_groups
    ADD CONSTRAINT rbac_user_groups_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.rbac_users(id) ON DELETE CASCADE;


--
-- Name: rbac_user_roles rbac_user_roles_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_user_roles
    ADD CONSTRAINT rbac_user_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.rbac_roles(id) ON DELETE CASCADE;


--
-- Name: rbac_user_roles rbac_user_roles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_user_roles
    ADD CONSTRAINT rbac_user_roles_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.rbac_users(id) ON DELETE CASCADE;


--
-- Name: rbac_users rbac_users_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.rbac_users
    ADD CONSTRAINT rbac_users_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: routes routes_service_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_service_id_fkey FOREIGN KEY (service_id, ws_id) REFERENCES public.services(id, ws_id);


--
-- Name: routes routes_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.routes
    ADD CONSTRAINT routes_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: services services_client_certificate_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_client_certificate_id_fkey FOREIGN KEY (client_certificate_id, ws_id) REFERENCES public.certificates(id, ws_id);


--
-- Name: services services_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.services
    ADD CONSTRAINT services_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: session_metadatas session_metadatas_session_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.session_metadatas
    ADD CONSTRAINT session_metadatas_session_id_fkey FOREIGN KEY (session_id) REFERENCES public.sessions(id) ON DELETE CASCADE;


--
-- Name: sm_vaults sm_vaults_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.sm_vaults
    ADD CONSTRAINT sm_vaults_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: snis snis_certificate_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.snis
    ADD CONSTRAINT snis_certificate_id_fkey FOREIGN KEY (certificate_id, ws_id) REFERENCES public.certificates(id, ws_id);


--
-- Name: snis snis_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.snis
    ADD CONSTRAINT snis_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: targets targets_upstream_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.targets
    ADD CONSTRAINT targets_upstream_id_fkey FOREIGN KEY (upstream_id, ws_id) REFERENCES public.upstreams(id, ws_id) ON DELETE CASCADE;


--
-- Name: targets targets_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.targets
    ADD CONSTRAINT targets_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: upstreams upstreams_client_certificate_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.upstreams
    ADD CONSTRAINT upstreams_client_certificate_id_fkey FOREIGN KEY (client_certificate_id) REFERENCES public.certificates(id);


--
-- Name: upstreams upstreams_ws_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.upstreams
    ADD CONSTRAINT upstreams_ws_id_fkey FOREIGN KEY (ws_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: workspace_entity_counters workspace_entity_counters_workspace_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: kong
--

ALTER TABLE ONLY public.workspace_entity_counters
    ADD CONSTRAINT workspace_entity_counters_workspace_id_fkey FOREIGN KEY (workspace_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

