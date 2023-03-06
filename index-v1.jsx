'use strict';

import PlacesContext  from './ctx-places.jsx';
import BackupsContext from './ctx-backups.jsx';

import { Tabs, Tab } from './tabs.jsx';
import { Places } from './places.jsx';


class BoardComponent extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
		};
	}

	render() {
	//	console.log(this.context);
		const ctx = this.context || {};
		return <div className="">
			<Tabs>
				<Tab title="Places">
					<Places />
				</Tab>
				<Tab title="Backups">
					<p>Hello world!</p>
				</Tab>
			</Tabs>
		</div>;
	}
}


class App extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			places:  {
				data: initPlaces,
				addPlace: info => {
					console.log('addPlace', info);
					const data = [...this.state.places.data];
					data.push(info);
					this.setState({places: { data }});
				},
			},
			backups: initBackups,
		};
	}

	render() {
		return <PlacesContext.Provider value={this.state.places}>
			<BoardComponent />
		</PlacesContext.Provider>;
	}
}

ReactDOM.render(<App />, document.getElementById('react-root'));

// oActions.init();
// initStock.init();
