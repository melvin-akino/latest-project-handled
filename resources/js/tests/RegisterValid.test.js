import Vue from 'vue'
import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)

import {mount} from '@vue/test-utils'
import Register from '../components/auth/Register.vue'

describe('Registration Tests For Valid Parameters', () => {
    it('Call the register function on form submit', () => {
        const wrapper = mount(Register)
        const register = jest.fn()
        wrapper.setMethods({register})
        wrapper.find('form').trigger('submit')
        expect(register).toHaveBeenCalled()
    })

    it('Register form proceed to register when all inputs are valid', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').setValue('Tony Stark')
        wrapper.find('#email').setValue('tonystark@marvel.com')
        wrapper.find('#password').setValue('iamironman')
        wrapper.find('#password_confirmation').setValue('iamironman')
        const register = jest.fn()
        wrapper.setMethods({register:register})
        // Mock axios call here
        wrapper.find('form').trigger('submit')
        expect(validation.$invalid).toBe(false)
    })

    const requiredformInputs = ['name', 'email', 'password']
    requiredformInputs.forEach(input => {
        it(`Required validation rule should be true on form submit when ${input} input has a value`, () => {
            const wrapper = mount(Register)
            const validation = wrapper.vm.$v.registerForm
            wrapper.find(`#${input}`).setValue('randomstring')
            wrapper.find('form').trigger('submit')
            expect(validation[input].required).toBe(true)
        })

        it(`Required validation rule should be true on typing when ${input} input has a value`, () => {
            const wrapper = mount(Register)
            const validation = wrapper.vm.$v.registerForm
            wrapper.find(`#${input}`).trigger('keydown.up')
            wrapper.find(`#${input}`).setValue('randomstring')
            expect(validation[input].required).toBe(true)
        })
    })

    it('Email validation rule should be true on form submit when email input is valid', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#email').setValue('test@mail.com')
        wrapper.find('form').trigger('submit')
        expect(validation.email.email).toBe(true)
    })

    it('Email validation rule should be true on typing when email input is valid', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#email').trigger('keydown.up')
        wrapper.find('#email').setValue('test@mail.com')
        expect(validation.email.email).toBe(true)
    })

    it('Password minimum of 6 validation rule should be true on form submit when password input has 6 or more characters', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password').setValue('123456')
        wrapper.find('form').trigger('submit')
        expect(validation.password.minLength).toBe(true)
    })

    it('Password minimum of 6 validation rule should be true on typing when password input has 6 or more characters', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password').trigger('keydown.up')
        wrapper.find('#password').setValue('123456')
        expect(validation.password.minLength).toBe(true)
    })

    it('Password "same as" validation rule should be true on form submit when password confirmation input and password input are the same', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password').setValue('123456')
        wrapper.find('#password_confirmation').setValue('123456')
        wrapper.find('form').trigger('submit')
        expect(validation.password_confirmation.sameAs).toBe(true)
    })

    it('Password "same as" validation rule should be true on typing when password confirmation input and password input are the same', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password_confirmation').trigger('keydown.up')
        wrapper.find('#password').setValue('123456')
        wrapper.find('#password_confirmation').setValue('123456')
        expect(validation.password_confirmation.sameAs).toBe(true)
    })
})
