/**
 * @jest-environment jsdom
 */

import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

// The real panel needs the whole editor store, a passthrough is enough here.
jest.mock( '@wordpress/editor', () => ( {
	PluginDocumentSettingPanel: ( { title, children } ) => (
		<section aria-label={ title }>{ children }</section>
	),
} ) );

// ClipboardButton is deprecated and logs on every render.
jest.mock( '@wordpress/deprecated', () => jest.fn() );

import ShortlinkPanel from '../shortlink-panel';

describe( 'ShortlinkPanel', () => {
	const shortlink = 'https://wjn.me/b/FJ';

	beforeEach( () => {
		window._humEditorObject = { shortlink };
	} );

	afterEach( () => {
		delete window._humEditorObject;
		jest.useRealTimers();
	} );

	test( 'shows the shortlink in a read-only field', () => {
		render( <ShortlinkPanel /> );

		const input = screen.getByRole( 'textbox', { name: 'Shortlink' } );

		expect( input ).toHaveValue( shortlink );
		expect( input ).toBeDisabled();
	} );

	test( 'copies the shortlink to the clipboard and confirms it', async () => {
		const user = userEvent.setup();
		render( <ShortlinkPanel /> );

		await user.click( screen.getByRole( 'button', { name: 'Copy link' } ) );

		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Copied!' } )
			).toBeInTheDocument()
		);
		expect( await navigator.clipboard.readText() ).toBe( shortlink );
	} );

	test( 'resets the button label after a few seconds', async () => {
		jest.useFakeTimers();
		const user = userEvent.setup( {
			advanceTimers: jest.advanceTimersByTime,
		} );
		render( <ShortlinkPanel /> );

		await user.click( screen.getByRole( 'button', { name: 'Copy link' } ) );
		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Copied!' } )
			).toBeInTheDocument()
		);

		act( () => {
			jest.advanceTimersByTime( 4000 );
		} );

		expect(
			screen.getByRole( 'button', { name: 'Copy link' } )
		).toBeInTheDocument();
	} );
} );
