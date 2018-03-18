/* columns named to follow total.sql */
CREATE TABLE %dbpre%totalspecials (
  ts_stype smallint NOT NULL,
  ts_score smallint NOT NULL default 0,
  ts_plr int NOT NULL,
  ts_gms int NOT NULL default 0,
  ts_tm bigint NOT NULL default 0
);

CREATE INDEX ts_ps ON %dbpre%totalspecials (ts_plr,ts_stype);
