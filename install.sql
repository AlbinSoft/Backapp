
CREATE TABLE `t_places` (
  `id_place` int NOT NULL AUTO_INCREMENT,
  `name` varchar(125) DEFAULT NULL,
  `location` varchar(125) DEFAULT NULL,
  `device` varchar(125) DEFAULT NULL,
  `drive` varchar(125) DEFAULT NULL,
  `path` varchar(225) DEFAULT NULL,
  `notes` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id_place`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `t_relations` (
  `id_relation` int NOT NULL AUTO_INCREMENT,
  `id_place_src` int DEFAULT NULL,
  `id_place_trg` int DEFAULT NULL,
  `name` varchar(125) DEFAULT NULL,
  `agent` varchar(125) DEFAULT NULL,
  `frequency` varchar(125) DEFAULT NULL,
  `notes` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id_relation`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

