import React from 'react';
import ReactDOM from 'react-dom';

import PlacesContext              from './ctx-places.jsx';
import BackupsContext             from './ctx-backups.jsx';
import { PlacesContextProvider  } from './ctx-places.jsx';
import { BackupsContextProvider } from './ctx-backups.jsx';

import { Tabs, Tab } from './tabs.jsx';
import { Places } from './places.jsx';
import { Backups } from './backups.jsx';


class BoardComponent extends React.Component {
	constructor(props) {
		super(props);
	}

	render() {
		return <React.Fragment>
			<Tabs>
				<Tab key="tab_places" title="Places">
					<Places />
				</Tab>
				<Tab key="tab_backups" title="Backups">
					<Backups />
				</Tab>
			</Tabs>
		</React.Fragment>;
	}
}


class App extends React.Component {
	constructor(props) {
		super(props);
	}

	render() {
		return <PlacesContextProvider>
			<BackupsContextProvider>
				<BoardComponent />
			</BackupsContextProvider>
		</PlacesContextProvider>;
	}
}

ReactDOM.render(<App />, document.getElementById('react-root'));
