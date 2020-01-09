import Vue from 'vue'
import Vuelidate from 'vuelidate'
Vue.use(Vuelidate)

import {mount} from '@vue/test-utils'
import ForgotPassword from '../components/auth/ForgotPassword.vue'

describe('Forgot password tests for invalid parameters', () => {
    it('Forgot password do not proceed to send email for password reset when email is empty', () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').setValue('')
        wrapper.find('form').trigger('submit')
        expect(validation.$invalid).toBe(true)
    })

    it('Forgot password do not proceed to send email for password reset when email is invalid', () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').setValue('randomstring')
        wrapper.find('form').trigger('submit')
        expect(validation.$invalid).toBe(true)
    })

    it('Required validation rule should be false and error message should show on form submit when email input is empty', async () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').setValue('')
        wrapper.find('form').trigger('submit')
        await Vue.nextTick()
        expect(wrapper.html()).toContain('Please type your email')
        expect(validation.required).toBe(false)
    })

    it('Required validation rule should be false and error message should show on typing when email input is empty', async () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').trigger('keydown.up')
        wrapper.find('#email').setValue('')
        await Vue.nextTick()
        expect(wrapper.html()).toContain('Please type your email')
        expect(validation.required).toBe(false)
    })

    it('Email validation rule should be false and error message should show on form submit when email input is invalid', async () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').setValue('randomstring')
        wrapper.find('form').trigger('submit')
        await Vue.nextTick()
        expect(wrapper.html()).toContain('Please type a valid email')
        expect(validation.email).toBe(false)
    })

    it('Email validation rule should be false and error message should show on typing when email input is invalid', async () => {
        const wrapper = mount(ForgotPassword)
        const validation = wrapper.vm.$v.email
        wrapper.find('#email').trigger('keydown.up')
        wrapper.find('#email').setValue('randomstring')
        await Vue.nextTick()
        expect(wrapper.html()).toContain('Please type a valid email')
        expect(validation.email).toBe(false)
    })
})
