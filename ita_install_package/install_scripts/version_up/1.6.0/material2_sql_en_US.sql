ALTER TABLE F_MATERIAL_LINKAGE_ANS ADD COLUMN ACCESS_AUTH TEXT AFTER ANSIBLE_ROLE_CHK;

ALTER TABLE F_MATERIAL_LINKAGE_ANS_JNL ADD COLUMN ACCESS_AUTH TEXT AFTER ANSIBLE_ROLE_CHK;



UPDATE A_SEQUENCE SET MENU_ID=2100150006, DISP_SEQ=2100520001, NOTE=NULL, LAST_UPDATE_TIMESTAMP=STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f') WHERE NAME='F_MATERIAL_LINKAGE_ANS_RIC';
UPDATE A_SEQUENCE SET MENU_ID=2100150006, DISP_SEQ=2100520002, NOTE='for the history table.', LAST_UPDATE_TIMESTAMP=STR_TO_DATE('2015/04/01 10:00:00.000000','%Y/%m/%d %H:%i:%s.%f') WHERE NAME='F_MATERIAL_LINKAGE_ANS_JSQ';
