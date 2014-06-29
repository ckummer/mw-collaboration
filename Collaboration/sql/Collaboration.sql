-- (c) 2013 by Christian Kummer
-- Table(s) for Collaboration
-- Replace /*_*/ with the proper prefix
-- Replace /*$wgDBTableOptions*/ with the correct options

-- Add the table for storing social network indices
CREATE TABLE IF NOT EXISTS /*_*/collaboration (
  row_names 			text,
  time 					text,
  pa 					bigint(20) 	DEFAULT NULL,
  den 					double 		DEFAULT NULL,
  cent 					double 		DEFAULT NULL,
  deg 					double 		DEFAULT NULL,
  dis 					bigint(20) 	DEFAULT NULL,
  calibrated_den 		double 		DEFAULT NULL,
  calibrated_cent 		double 		DEFAULT NULL,
  calibrated_deg 		double 		DEFAULT NULL,
  calibrated_dis 		double 		DEFAULT NULL,
  collaboration_final 	double 		DEFAULT NULL
) /*$wgDBTableOptions*/;