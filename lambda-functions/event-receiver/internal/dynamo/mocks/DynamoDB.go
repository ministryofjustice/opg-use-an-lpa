// Code generated by mockery v2.51.0. DO NOT EDIT.

package mocks

import (
	context "context"

	dynamodb "github.com/aws/aws-sdk-go-v2/service/dynamodb"

	mock "github.com/stretchr/testify/mock"
)

// DynamoDB is an autogenerated mock type for the DynamoDB type
type DynamoDB struct {
	mock.Mock
}

// GetItem provides a mock function with given fields: _a0, _a1, _a2
func (_m *DynamoDB) GetItem(_a0 context.Context, _a1 *dynamodb.GetItemInput, _a2 ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error) {
	_va := make([]interface{}, len(_a2))
	for _i := range _a2 {
		_va[_i] = _a2[_i]
	}
	var _ca []interface{}
	_ca = append(_ca, _a0, _a1)
	_ca = append(_ca, _va...)
	ret := _m.Called(_ca...)

	if len(ret) == 0 {
		panic("no return value specified for GetItem")
	}

	var r0 *dynamodb.GetItemOutput
	var r1 error
	if rf, ok := ret.Get(0).(func(context.Context, *dynamodb.GetItemInput, ...func(*dynamodb.Options)) (*dynamodb.GetItemOutput, error)); ok {
		return rf(_a0, _a1, _a2...)
	}
	if rf, ok := ret.Get(0).(func(context.Context, *dynamodb.GetItemInput, ...func(*dynamodb.Options)) *dynamodb.GetItemOutput); ok {
		r0 = rf(_a0, _a1, _a2...)
	} else {
		if ret.Get(0) != nil {
			r0 = ret.Get(0).(*dynamodb.GetItemOutput)
		}
	}

	if rf, ok := ret.Get(1).(func(context.Context, *dynamodb.GetItemInput, ...func(*dynamodb.Options)) error); ok {
		r1 = rf(_a0, _a1, _a2...)
	} else {
		r1 = ret.Error(1)
	}

	return r0, r1
}

// PutItem provides a mock function with given fields: _a0, _a1, _a2
func (_m *DynamoDB) PutItem(_a0 context.Context, _a1 *dynamodb.PutItemInput, _a2 ...func(*dynamodb.Options)) (*dynamodb.PutItemOutput, error) {
	_va := make([]interface{}, len(_a2))
	for _i := range _a2 {
		_va[_i] = _a2[_i]
	}
	var _ca []interface{}
	_ca = append(_ca, _a0, _a1)
	_ca = append(_ca, _va...)
	ret := _m.Called(_ca...)

	if len(ret) == 0 {
		panic("no return value specified for PutItem")
	}

	var r0 *dynamodb.PutItemOutput
	var r1 error
	if rf, ok := ret.Get(0).(func(context.Context, *dynamodb.PutItemInput, ...func(*dynamodb.Options)) (*dynamodb.PutItemOutput, error)); ok {
		return rf(_a0, _a1, _a2...)
	}
	if rf, ok := ret.Get(0).(func(context.Context, *dynamodb.PutItemInput, ...func(*dynamodb.Options)) *dynamodb.PutItemOutput); ok {
		r0 = rf(_a0, _a1, _a2...)
	} else {
		if ret.Get(0) != nil {
			r0 = ret.Get(0).(*dynamodb.PutItemOutput)
		}
	}

	if rf, ok := ret.Get(1).(func(context.Context, *dynamodb.PutItemInput, ...func(*dynamodb.Options)) error); ok {
		r1 = rf(_a0, _a1, _a2...)
	} else {
		r1 = ret.Error(1)
	}

	return r0, r1
}

// Query provides a mock function with given fields: _a0, _a1, _a2
func (_m *DynamoDB) Query(_a0 context.Context, _a1 *dynamodb.QueryInput, _a2 ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error) {
	_va := make([]interface{}, len(_a2))
	for _i := range _a2 {
		_va[_i] = _a2[_i]
	}
	var _ca []interface{}
	_ca = append(_ca, _a0, _a1)
	_ca = append(_ca, _va...)
	ret := _m.Called(_ca...)

	if len(ret) == 0 {
		panic("no return value specified for Query")
	}

	var r0 *dynamodb.QueryOutput
	var r1 error
	if rf, ok := ret.Get(0).(func(context.Context, *dynamodb.QueryInput, ...func(*dynamodb.Options)) (*dynamodb.QueryOutput, error)); ok {
		return rf(_a0, _a1, _a2...)
	}
	if rf, ok := ret.Get(0).(func(context.Context, *dynamodb.QueryInput, ...func(*dynamodb.Options)) *dynamodb.QueryOutput); ok {
		r0 = rf(_a0, _a1, _a2...)
	} else {
		if ret.Get(0) != nil {
			r0 = ret.Get(0).(*dynamodb.QueryOutput)
		}
	}

	if rf, ok := ret.Get(1).(func(context.Context, *dynamodb.QueryInput, ...func(*dynamodb.Options)) error); ok {
		r1 = rf(_a0, _a1, _a2...)
	} else {
		r1 = ret.Error(1)
	}

	return r0, r1
}

// NewDynamoDB creates a new instance of DynamoDB. It also registers a testing interface on the mock and a cleanup function to assert the mocks expectations.
// The first argument is typically a *testing.T value.
func NewDynamoDB(t interface {
	mock.TestingT
	Cleanup(func())
}) *DynamoDB {
	mock := &DynamoDB{}
	mock.Mock.Test(t)

	t.Cleanup(func() { mock.AssertExpectations(t) })

	return mock
}
