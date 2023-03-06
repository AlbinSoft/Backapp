import React from 'react';

class Tabs extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			current: 0,
		};
	}

	render() {
		return <div className="tabs_wrap">
			<div className="tabs_nav">
			{this.props.children.map((child, idx) => {
				return <span className={`${this.state.current==idx ? 'sel': 'uns'}`} onClick={ () => this.setState({ current: idx }) }>{child.props.title}</span>;
			})}
			</div>
			{this.props.children[this.state.current]}
		</div>;
	}
}

class Tab extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
		};
	}

	render() {
		return <div className="tabs_cont">
			{this.props.children}
		</div>;
	}
}

export { Tabs, Tab };
