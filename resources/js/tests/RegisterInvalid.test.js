import Vue from 'vue'
import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)

import {mount} from '@vue/test-utils'
import Register from '../components/auth/Register.vue'

describe('Registration Tests For Invalid Parameters', () => {
    it('Register form do not proceed to register when all inputs are invalid', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').setValue('')
        wrapper.find('#email').setValue('tonystark')
        wrapper.find('#password').setValue('123')
        wrapper.find('#password_confirmation').setValue('123')
        wrapper.find('form').trigger('submit')
        expect(validation.$invalid).toBe(true)
    })

    it('Register form do not proceed to register when some inputs are invalid', () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#name').setValue('Tony Stark')
        wrapper.find('#email').setValue('tonystark')
        wrapper.find('#password').setValue('12345')
        wrapper.find('#password_confirmation').setValue('123')
        wrapper.find('form').trigger('submit')
        expect(validation.$invalid).toBe(true)
    })

    const requiredformInputs = ['name', 'email', 'password']
    requiredformInputs.forEach(input => {
        it(`Required validation rule should be false and error message should show on form submit when ${input} input is empty`, async () => {
            const wrapper = mount(Register)
            const validation = wrapper.vm.$v.registerForm
            wrapper.find(`#${input}`).setValue('')
            wrapper.find('form').trigger('submit')
            await Vue.nextTick()
            expect(validation[input].required).toBe(false)

            if(input==='name') {
                expect(wrapper.html()).toContain('Please type your name')
            } else if(input==='email') {
                expect(wrapper.html()).toContain('Please type an email')
            } else if(input==='password') {
                expect(wrapper.html()).toContain('Please type a password')
            }
        })

        it(`Required validation rule should be false and error message should show on typing when ${input} input is empty`, async () => {
            const wrapper = mount(Register)
            const validation = wrapper.vm.$v.registerForm
            wrapper.find(`#${input}`).trigger('keydown.up')
            wrapper.find(`#${input}`).setValue('')
            await Vue.nextTick()
            expect(validation[input].required).toBe(false)

            if(input==='name') {
                expect(wrapper.html()).toContain('Please type your name')
            } else if(input==='email') {
                expect(wrapper.html()).toContain('Please type an email')
            } else if(input==='password') {
                expect(wrapper.html()).toContain('Please type a password')
            }
        })
    })

    it('Email validation rule should be false and error message should show on form submit when email input is invalid', async () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#email').setValue('randomstring')
        wrapper.find('form').trigger('submit')
        await Vue.nextTick()
        expect(validation.email.email).toBe(false)
        expect(wrapper.html()).toContain('Please type a valid email')
    })

    it('Email validation rule should be false and error message should show on typing when email input is invalid', async () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#email').trigger('keydown.up')
        wrapper.find('#email').setValue('randomstring')
        await Vue.nextTick()
        expect(validation.email.email).toBe(false)
        expect(wrapper.html()).toContain('Please type a valid email')
    })

    it('Password minimum of 6 validation rule should be false and error message should show on form submit when password input is less than 6 characters', async () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password').setValue('1234')
        wrapper.find('form').trigger('submit')
        await Vue.nextTick()
        expect(validation.password.minLength).toBe(false)
        expect(wrapper.html()).toContain('Password must have a minimum of 6 characters')
    })

    it('Password minimum of 6 validation rule should be false and error message should show on typing when password input is less than 6 characters', async () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password').trigger('keydown.up')
        wrapper.find('#password').setValue('1234')
        await Vue.nextTick()
        expect(validation.password.minLength).toBe(false)
        expect(wrapper.html()).toContain('Password must have a minimum of 6 characters')
    })

    it('Password "same as" validation rule should be false and error message should show on form submit when password confirmation input and password input are not the same', async () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password').setValue('1234')
        wrapper.find('#password_confirmation').setValue('123456')
        wrapper.find('form').trigger('submit')
        await Vue.nextTick()
        expect(validation.password_confirmation.sameAs).toBe(false)
        expect(wrapper.html()).toContain('Password does not match')
    })

    it('Password "same as" validation rule should be false and error message should show on typing when password confirmation input and password input are not the same', async () => {
        const wrapper = mount(Register)
        const validation = wrapper.vm.$v.registerForm
        wrapper.find('#password_confirmation').trigger('keydown.up')
        wrapper.find('#password').setValue('1234')
        wrapper.find('#password_confirmation').setValue('123456')
        await Vue.nextTick()
        expect(validation.password_confirmation.sameAs).toBe(false)
        expect(wrapper.html()).toContain('Password does not match')
    })
})
