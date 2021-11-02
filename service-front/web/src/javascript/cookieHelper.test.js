import { getCookie, setCookie, createCookie, approveAllCookieTypes, setDefaultConsentCookie } from './cookieHelper';

describe('When I get a cookie', () => {
    delete global.window.location;
    global.window = Object.create(window);
    global.window.location = {
        port: '80',
        protocol: 'https:',
        hostname: 'localhost',
        pathname: '/'
    };

    describe('and it exists', () => {
        beforeEach(() => {
            const gettersSetters = {
                get: jest.fn().mockImplementation(() => {
                    return 'true'
                }),
            set: jest.fn()
            }
            Object.defineProperty(document, 'cookie', {
                value: gettersSetters,
                writable: true,
                configurable: true,
                enumerable: true

            });
        })
        test('it should return the correct value', () => {
            setCookie('test', 'true');
            const cookieValue = getCookie('test');
            expect(cookieValue).toBe('true');
        });

    });
    describe('and it does not exist', () => {
        test('it should return null', () => {
            const cookieValue = getCookie('test2');
            expect(cookieValue).toBeNull();
        });
    });
});

describe('When I create a cookie', () => {
    beforeEach(() => {
        jest
        .spyOn(global.Date, 'now')
        .mockImplementation(
            () =>
            new Date('2019-05-14T11:01:58.135Z').valueOf()
        );
    })

  describe('with no days specified', () => {
        test('it should return the correct format for 30 days ahead', () => {
            const cookieValue = createCookie('test_cookie', 'test value');
            expect(cookieValue).toBe('test_cookie=test%20value; path=/; expires=Thu, 13 Jun 2019 11:01:58 GMT; Secure');
        });
    });
  describe('with 10 days specified', () => {
        test('it should return the correct format for 30 days ahead', () => {
            const cookieValue = createCookie('test_cookie', 'test value', { days: 10 });
            expect(cookieValue).toBe('test_cookie=test%20value; path=/; expires=Fri, 24 May 2019 11:01:58 GMT; Secure');
        });
    });
  describe('with an empty options parameter', () => {
        test('it should return the correct format for 30 days ahead', () => {
            const cookieValue = createCookie('test_cookie', 'test value', { });
            expect(cookieValue).toBe('test_cookie=test%20value; path=/; expires=Thu, 13 Jun 2019 11:01:58 GMT; Secure');
        });
    });
  describe('with an incorrect options parameter', () => {
        test('it should return the correct format for 30 days ahead', () => {
            const cookieValue = createCookie('test_cookie', 'test value', { wrongOption: '' });
            expect(cookieValue).toBe('test_cookie=test%20value; path=/; expires=Thu, 13 Jun 2019 11:01:58 GMT; Secure');
        });
    });
});

describe('When I call setCookie', () => {

    beforeEach(() => {
        jest
        .spyOn(global.Date, 'now')
        .mockImplementation(
            () =>
            new Date('2019-05-14T11:01:58.135Z').valueOf()
        );
        const gettersSetters = {
            value: '',
            get: jest.fn().mockImplementation(() => {
                return this.value;
            }),
        set: jest.fn().mockImplementation((val) => {
            this.value = val;
        }),
        }
        Object.defineProperty(document, 'cookie', {
            value: gettersSetters,
            writable: true,
            configurable: true,
            enumerable: true
        });
    })
    test('it should set a cookie', () => {
        setCookie('test_cookie', 'test value');
        const cookieValue = getCookie('test_cookie');
        expect(cookieValue).toBe('test value');
        expect(document.cookie).toBe('test_cookie=test%20value; path=/; expires=Thu, 13 Jun 2019 11:01:58 GMT; Secure');
    });
});

describe('When I call approveAllCookieTypes', () => {
    beforeEach(() => {
        const gettersSetters = {
            value: '',
            get: jest.fn().mockImplementation(() => {
                return this.value;
            }),
        set: jest.fn().mockImplementation((val) => {
            this.value = val;
          }),
        }
        Object.defineProperty(document, 'cookie', {
            value: gettersSetters,
            writable: true,
            configurable: true,
            enumerable: true
        });
    })
    test('it should set a cookie_policy cookie', () => {
        approveAllCookieTypes();
        const cookieValue = getCookie('cookie_policy');
        expect(cookieValue).not.toBeNull();
        expect(document.cookie).not.toBeNull();
        expect(JSON.parse(cookieValue)).toEqual({
            'essential': true,
            'usage': true
        });
    });
});

describe('When I call setDefaultConsentCookie', () => {
    beforeEach(() => {
        const gettersSetters = {
            value: '',
            get: jest.fn().mockImplementation(() => {
                return this.value;
            }),
        set: jest.fn().mockImplementation((val) => {
            this.value = val;
          }),
        }
        Object.defineProperty(document, 'cookie', {
            value: gettersSetters,
            writable: true,
            configurable: true,
            enumerable: true
        });
    })
  test('it should set a cookie_policy cookie', () => {
        setDefaultConsentCookie();

        const cookieValue = getCookie('cookie_policy');
        expect(cookieValue).not.toBeNull();
        expect(document.cookie).not.toBeNull();
        expect(JSON.parse(cookieValue)).toEqual({
            'essential': true,
            'usage': false
        });
    });
});
