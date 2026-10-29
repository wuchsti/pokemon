-- Phase 4: öffentlicher Sammler-Link.
-- Nur für bereits bestehende Installationen nötig - database/schema.sql
-- enthält die Spalte für neue Installationen schon direkt in der
-- users-Tabelle (CREATE TABLE IF NOT EXISTS legt sie dort nicht nachträglich an).

ALTER TABLE users
    ADD COLUMN public_binder_enabled BOOLEAN NOT NULL DEFAULT FALSE;
