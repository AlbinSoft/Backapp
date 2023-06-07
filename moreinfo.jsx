import React from 'react';

import icons from './icons.jsx';
// import './MoreInfo.module.css';
import * as styles from './moreinfo.module.css';

const MoreInfo = (props) => {

	const show_sources = props.sources && props.sources.length;
	const show_targets = props.targets && props.targets.length;
	const show_empty   = props.sources.length==0 && props.targets.length==0;

	console.log('styles', styles);

	return <div className={styles.minfoplace}>
		<span onClick={ e => props.close()  }>&times;</span>
		{ show_empty &&
			<p className="empty">It doesn´t belong to a backup</p>
		}
		{ show_sources && <>
			<p className="title">Is source:</p>
			<ul className="list">
				{ props.sources.map(bup => {
					return <li>
						{ bup.location_src }
						{ bup.device_src }
						{ bup.drive_src }
						{ bup.path_src }
					</li>;
				} ) }
			</ul>
		</> }
		{ show_targets && <>
			<p className="title">Is target:</p>
			<ul className="list">
				{ props.targets.map(bup => {
					return <li>
						{ bup.location_trg }
						{ bup.device_trg }
						{ bup.drive_trg }
						{ bup.path_trg }
					</li>;
				} ) }
			</ul>
		</> }
	</div>;
};

export default MoreInfo;
